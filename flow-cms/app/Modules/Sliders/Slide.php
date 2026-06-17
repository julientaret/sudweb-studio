<?php

declare(strict_types=1);

namespace App\Modules\Sliders;

/**
 * Entité diapositive (module Sliders).
 *
 * `description` est un Delta Quill décodé depuis le JSON ({ops:[...]}), rendu
 * côté serveur par App\Core\DeltaRenderer (prose bornée, jamais de HTML stocké).
 * `mediaId` référence une photo par ID média (jamais d'URL). `position` ordonne
 * les diapositives (croissant). Le module n'expose pas d'URL front : pas de slug.
 */
final class Slide
{
    /**
     * @param array<string, mixed> $description Delta Quill décodé ({ops:[...]})
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly array $description,
        public readonly ?int $mediaId,
        public readonly int $position,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $delta = json_decode((string) $row['description'], true);
        $media = $row['media_id'] ?? null;
        return new self(
            (int) $row['id'],
            (string) $row['title'],
            is_array($delta) ? $delta : ['ops' => []],
            $media !== null && $media !== '' ? (int) $media : null,
            (int) $row['position'],
            (string) $row['status'],
            (string) $row['created_at'],
            (string) $row['updated_at'],
        );
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
