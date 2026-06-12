<?php

declare(strict_types=1);

namespace App\Modules\Media;

/**
 * Dossier de classement de la médiathèque (groupement logique).
 */
final class MediaFolder
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $createdAt,
        public readonly int $count = 0, // nombre de médias (rempli par le repo)
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['created_at'],
            (int) ($row['count'] ?? 0),
        );
    }
}
