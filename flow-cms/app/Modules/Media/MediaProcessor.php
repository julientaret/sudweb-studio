<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Config;
use App\Core\Kernel;
use GdImage;
use RuntimeException;

/**
 * Traitement des images (brief §5).
 *
 * Sécurité upload : on ne fait jamais confiance à l'extension ni au Content-Type.
 * Le type réel est vérifié via finfo, puis l'image est REPASSÉE par GD
 * (decode → re-encode) — ce qui réécrit le fichier proprement et détruit tout
 * payload planqué. S'ajoutent : refus de tout non-image, limite de taille, et
 * l'exécution PHP désactivée sur uploads/ (.htaccess).
 *
 * GD est la cible garantie. WebP en sortie en plus du format d'origine.
 */
final class MediaProcessor
{
    private string $uploadsDir;

    public function __construct()
    {
        $path = (string) Config::get('storage.uploads.path', '');
        $this->uploadsDir = $path !== ''
            ? rtrim($path, '/')
            : Kernel::instance()->basePath() . '/public/uploads';
    }

    /**
     * Valide, re-encode et stocke un fichier uploadé. Génère le WebP plein format
     * et toutes les déclinaisons. Renvoie les données prêtes pour MediaRepository.
     *
     * @return array{filename:string,original_name:string,mime:string,width:int,height:int,size:int,alt:string}
     */
    public function process(string $tmpPath, string $originalName, int $reportedSize): array
    {
        $maxSize = (int) Config::get('media.max_size', 5 * 1024 * 1024);
        $actualSize = is_file($tmpPath) ? (int) filesize($tmpPath) : $reportedSize;
        if ($actualSize <= 0 || $actualSize > $maxSize) {
            throw new RuntimeException('Fichier trop volumineux ou vide.');
        }

        // Type RÉEL via finfo (jamais l'extension ni le Content-Type).
        $allowed = (array) Config::get('media.allowed_mimes', []);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: '';
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Type de fichier non autorisé : ' . $mime);
        }
        $ext = $allowed[$mime];

        // Decode par GD : si ça échoue, ce n'est pas une image exploitable → refus.
        $bytes = file_get_contents($tmpPath);
        $img = $bytes !== false ? @imagecreatefromstring($bytes) : false;
        if (!$img instanceof GdImage) {
            throw new RuntimeException('Image illisible ou corrompue.');
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }

        $width = imagesx($img);
        $height = imagesy($img);

        // Dossier année/mois (jamais un dossier unique qui gonfle).
        $relDir = date('Y/m');
        $absDir = $this->uploadsDir . '/' . $relDir;
        $this->ensureDir($absDir);

        // Renommage systématique : slug + hash court. Jamais le nom client brut.
        $base = slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(4));
        $relFilename = $relDir . '/' . $base . '.' . $ext;

        // Original re-encodé (source propre, intacte ensuite) + WebP + déclinaisons.
        $this->writeOriginal($img, $absDir . '/' . $base . '.' . $ext, $ext);
        $this->regenerateVariants($img, $absDir, $base);

        imagedestroy($img);

        return [
            'filename'      => $relFilename,
            'original_name' => $originalName,
            'mime'          => $mime,
            'width'         => $width,
            'height'        => $height,
            'size'          => (int) filesize($absDir . '/' . $base . '.' . $ext),
            'alt'           => '',
        ];
    }

    /**
     * Régénère le WebP plein format et toutes les déclinaisons d'un média
     * existant, à partir de son original sur disque (brief §5 : script obligatoire).
     *
     * L'original n'est jamais touché. Si un recadrage est défini, il est ré-appliqué
     * ici avant de produire les variantes : le crop est donc durable ET survit à
     * media:regen.
     */
    public function regenerate(Media $media): bool
    {
        $absOriginal = $this->uploadsDir . '/' . $media->filename;
        if (!is_file($absOriginal)) {
            return false;
        }
        $bytes = file_get_contents($absOriginal);
        $img = $bytes !== false ? @imagecreatefromstring($bytes) : false;
        if (!$img instanceof GdImage) {
            return false;
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }

        // Recadrage non destructif appliqué sur une copie en mémoire.
        $source = $img;
        if ($media->hasCrop()) {
            $cropped = $this->cropImage($img, $media->cropX ?? 0, $media->cropY ?? 0, $media->cropW, $media->cropH);
            imagedestroy($img);
            $source = $cropped;
        }

        $absDir = $this->uploadsDir . '/' . $media->dir();
        $this->regenerateVariants($source, $absDir, $media->base());
        imagedestroy($source);
        return true;
    }

    /**
     * Découpe un rectangle de l'image, en bornant les coordonnées à l'image
     * (le front peut envoyer des valeurs débordantes après zoom/déplacement).
     */
    private function cropImage(GdImage $src, int $x, int $y, ?int $w, ?int $h): GdImage
    {
        $sw = imagesx($src);
        $sh = imagesy($src);

        $x = max(0, min($x, $sw - 1));
        $y = max(0, min($y, $sh - 1));
        $w = max(1, min((int) $w, $sw - $x));
        $h = max(1, min((int) $h, $sh - $y));

        $dst = $this->canvas($w, $h);
        imagecopy($dst, $src, 0, 0, $x, $y, $w, $h);
        return $dst;
    }

    /**
     * Supprime du disque l'original, le WebP plein format et les déclinaisons.
     */
    public function deleteFiles(Media $media): void
    {
        $absDir = $this->uploadsDir . '/' . $media->dir();
        $base = $media->base();

        @unlink($this->uploadsDir . '/' . $media->filename); // original
        @unlink($absDir . '/' . $base . '.webp');            // webp plein format
        foreach (array_keys((array) Config::get('media.sizes', [])) as $name) {
            @unlink($absDir . '/' . $base . '-' . $name . '.webp');
        }
    }

    // --- Interne ----------------------------------------------------------

    private function regenerateVariants(GdImage $img, string $absDir, string $base): void
    {
        // WebP plein format (en plus du format d'origine).
        $this->writeWebp($img, $absDir . '/' . $base . '.webp');

        $sw = imagesx($img);
        $sh = imagesy($img);
        foreach ((array) Config::get('media.sizes', []) as $name => $preset) {
            $variant = $this->resize($img, $sw, $sh, $preset);
            $this->writeWebp($variant, $absDir . '/' . $base . '-' . $name . '.webp');
            imagedestroy($variant);
        }
    }

    /**
     * @param array{w:int,h:?int,mode:string} $preset
     */
    private function resize(GdImage $src, int $sw, int $sh, array $preset): GdImage
    {
        $mode = $preset['mode'] ?? 'fit';
        $tw = (int) $preset['w'];
        $th = $preset['h'] !== null ? (int) $preset['h'] : null;

        if ($mode === 'cover') {
            $th ??= $tw;
            $ratio = max($tw / $sw, $th / $sh);
            $cropW = (int) round($tw / $ratio);
            $cropH = (int) round($th / $ratio);
            $srcX = (int) floor(($sw - $cropW) / 2);
            $srcY = (int) floor(($sh - $cropH) / 2);
            $dst = $this->canvas($tw, $th);
            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, $cropW, $cropH);
            return $dst;
        }

        // fit : garde le ratio, ne jamais agrandir au-delà de l'original.
        if ($th === null) {
            if ($sw <= $tw) {
                $newW = $sw;
                $newH = $sh;
            } else {
                $newW = $tw;
                $newH = (int) round($sh * $tw / $sw);
            }
        } else {
            $scale = min($tw / $sw, $th / $sh, 1.0);
            $newW = max(1, (int) round($sw * $scale));
            $newH = max(1, (int) round($sh * $scale));
        }

        $dst = $this->canvas($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $sw, $sh);
        return $dst;
    }

    /** Canvas truecolor avec transparence préservée. */
    private function canvas(int $w, int $h): GdImage
    {
        $c = imagecreatetruecolor($w, $h);
        imagealphablending($c, false);
        imagesavealpha($c, true);
        $transparent = imagecolorallocatealpha($c, 0, 0, 0, 127);
        imagefilledrectangle($c, 0, 0, $w, $h, $transparent);
        return $c;
    }

    private function writeOriginal(GdImage $img, string $absPath, string $ext): void
    {
        switch ($ext) {
            case 'jpg':
                imagejpeg($img, $absPath, (int) Config::get('media.jpeg_quality', 85));
                break;
            case 'png':
                imagesavealpha($img, true);
                imagepng($img, $absPath);
                break;
            case 'gif':
                imagegif($img, $absPath);
                break;
            case 'webp':
                imagewebp($img, $absPath, (int) Config::get('media.webp_quality', 82));
                break;
            default:
                throw new RuntimeException('Format non géré : ' . $ext);
        }
    }

    private function writeWebp(GdImage $img, string $absPath): void
    {
        imagesavealpha($img, true);
        imagewebp($img, $absPath, (int) Config::get('media.webp_quality', 82));
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossible de créer le dossier : ' . $dir);
        }
    }
}
