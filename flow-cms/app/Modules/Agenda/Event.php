<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

/**
 * Entité événement (module Agenda).
 *
 * `content` est le contenu page-builder décodé depuis le JSON (même format que
 * Pages : lignes → colonnes → blocs, voir App\Core\BlockContent). Les dates sont
 * stockées en `Y-m-d` ; `thumbnailId` référence un média par ID (jamais d'URL).
 */
final class Event
{
    /**
     * @param array<int, array<string, mixed>> $content
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly ?int $thumbnailId,
        public readonly array $content,
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
        $content = json_decode((string) $row['content'], true);
        $thumb = $row['thumbnail_id'] ?? null;
        $end = $row['end_date'] ?? null;
        return new self(
            (int) $row['id'],
            (string) $row['title'],
            (string) $row['slug'],
            (string) $row['start_date'],
            $end !== null && $end !== '' ? (string) $end : null,
            $thumb !== null && $thumb !== '' ? (int) $thumb : null,
            is_array($content) ? $content : [],
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
