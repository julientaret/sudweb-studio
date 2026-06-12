<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Config;
use RuntimeException;

/**
 * Validation et stockage des documents.
 *
 * Pendant « non-image » de MediaProcessor : aucun re-encodage possible (ce ne sont
 * pas des images), donc la sécurité repose sur une DOUBLE liste blanche —
 * extension ET type réel (finfo) — plus la coupure de l'exécution de scripts sur
 * frontend/public/documents/ (.htaccess) et le refus de tout ce qui n'est pas un véritable
 * upload HTTP (is_uploaded_file, vérifié par le contrôleur).
 *
 * Comme pour les médias, le fichier est renommé systématiquement (slug + hash
 * court) et rangé en sous-dossier année/mois ; le vrai nom reste en DB.
 */
final class DocumentProcessor
{
    private string $documentsDir;

    public function __construct()
    {
        $this->documentsDir = DocumentStorage::diskDir();
    }

    /**
     * Valide et stocke un fichier uploadé. Renvoie les données prêtes pour
     * DocumentRepository.
     *
     * @return array{filename:string,original_name:string,title:string,mime:string,ext:string,size:int}
     */
    public function process(string $tmpPath, string $originalName, int $reportedSize): array
    {
        $maxSize = (int) Config::get('documents.max_size', 20 * 1024 * 1024);
        $actualSize = is_file($tmpPath) ? (int) filesize($tmpPath) : $reportedSize;
        if ($actualSize <= 0 || $actualSize > $maxSize) {
            throw new RuntimeException('Fichier trop volumineux ou vide.');
        }

        // 1er garde-fou : l'extension doit figurer dans la liste blanche.
        $allowed = (array) Config::get('documents.allowed', []);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset($allowed[$ext])) {
            throw new RuntimeException('Type de fichier non autorisé : .' . ($ext !== '' ? $ext : '(inconnu)'));
        }

        // 2nd garde-fou : le type RÉEL via finfo (jamais le Content-Type du client).
        $allowedMimes = (array) Config::get('documents.allowed_mimes', []);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: '';
        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('Contenu du fichier non autorisé : ' . $mime);
        }

        // Dossier année/mois (jamais un dossier unique qui gonfle).
        $relDir = date('Y/m');
        $absDir = $this->documentsDir . '/' . $relDir;
        $this->ensureDir($absDir);

        // Renommage systématique : slug + hash court. Jamais le nom client brut.
        $base = slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(4));
        $relFilename = $relDir . '/' . $base . '.' . $ext;
        $absPath = $absDir . '/' . $base . '.' . $ext;

        if (!move_uploaded_file($tmpPath, $absPath)) {
            // Repli pour les chemins non-upload (tests, CLI) : copie simple.
            if (!@copy($tmpPath, $absPath)) {
                throw new RuntimeException('Impossible d’enregistrer le document.');
            }
        }
        @chmod($absPath, 0644);

        return [
            'filename'      => $relFilename,
            'original_name' => $originalName,
            'title'         => '',
            'mime'          => $mime,
            'ext'           => $ext,
            'size'          => (int) filesize($absPath),
        ];
    }

    /** Supprime du disque le fichier d'un document. */
    public function deleteFiles(Document $document): void
    {
        @unlink($this->documentsDir . '/' . $document->filename);
    }

    /**
     * Limite réellement applicable à un upload : le plus petit entre notre plafond
     * de config et les limites PHP du SAPI courant (`upload_max_filesize` et
     * `post_max_size`). C'est ce chiffre qu'on affiche et qu'on contrôle côté
     * client, pour ne jamais promettre plus que ce que le serveur accepte.
     */
    public static function effectiveMaxSize(): int
    {
        $config = (int) Config::get('documents.max_size', 20 * 1024 * 1024);
        return min($config, self::iniLimitBytes());
    }

    /** Plus petite des limites PHP d'upload (octets). PHP_INT_MAX si non bornées. */
    public static function iniLimitBytes(): int
    {
        $limits = array_filter([
            self::parseIniSize((string) ini_get('upload_max_filesize')),
            self::parseIniSize((string) ini_get('post_max_size')),
        ], static fn (int $v): bool => $v > 0);
        return $limits === [] ? PHP_INT_MAX : (int) min($limits);
    }

    /** Convertit une taille ini abrégée (« 2M », « 8M », « 1G ») en octets. */
    private static function parseIniSize(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $num = (int) $value;
        return match (strtolower($value[strlen($value) - 1])) {
            'g'     => $num * 1024 * 1024 * 1024,
            'm'     => $num * 1024 * 1024,
            'k'     => $num * 1024,
            default => (int) $value,
        };
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossible de créer le dossier : ' . $dir);
        }
    }
}
