<?php

declare(strict_types=1);

namespace App\Modules\Media;

/**
 * Entité média (brief §5).
 *
 * Porte l'enregistrement de la table `media`. Les chemins des déclinaisons et
 * des versions WebP sont DÉRIVÉS du filename (pas stockés) : voir Media::path()
 * et MediaUrl.
 */
final class Media
{
    public function __construct(
        public readonly int $id,
        public readonly string $filename,      // relatif à public/uploads/, ex. "2026/06/x.jpg"
        public readonly string $originalName,
        public readonly string $mime,
        public readonly int $width,            // dimensions de l'original (source de vérité)
        public readonly int $height,
        public readonly int $size,
        public readonly string $alt,
        public readonly string $createdAt,
        // Recadrage non destructif, en pixels de l'original ; null = pas de crop.
        public readonly ?int $cropX = null,
        public readonly ?int $cropY = null,
        public readonly ?int $cropW = null,
        public readonly ?int $cropH = null,
        // Dossier de classement (null = « Non classé »).
        public readonly ?int $folderId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['filename'],
            (string) $row['original_name'],
            (string) $row['mime'],
            (int) $row['width'],
            (int) $row['height'],
            (int) $row['size'],
            (string) $row['alt'],
            (string) $row['created_at'],
            isset($row['crop_x']) ? (int) $row['crop_x'] : null,
            isset($row['crop_y']) ? (int) $row['crop_y'] : null,
            isset($row['crop_w']) ? (int) $row['crop_w'] : null,
            isset($row['crop_h']) ? (int) $row['crop_h'] : null,
            isset($row['folder_id']) ? (int) $row['folder_id'] : null,
        );
    }

    /** Un recadrage valide est-il défini ? */
    public function hasCrop(): bool
    {
        return $this->cropW !== null && $this->cropH !== null
            && $this->cropW > 0 && $this->cropH > 0;
    }

    /** Dossier relatif (ex. "2026/06"). */
    public function dir(): string
    {
        return trim(dirname($this->filename), '.');
    }

    /** Nom de base sans extension (ex. "photo-ab12cd34"). */
    public function base(): string
    {
        return pathinfo($this->filename, PATHINFO_FILENAME);
    }
}
