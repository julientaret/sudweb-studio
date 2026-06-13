<?php

declare(strict_types=1);

namespace App\Modules\LegalPages;

/**
 * Entité page légale (module Pages légales).
 *
 * `content` est le Delta Quill décodé depuis le JSON ({"ops":[...]}), rendu en
 * HTML borné par App\Core\DeltaRenderer. `slug` unique = URL front
 * (/<slug>). `status` pilote la visibilité publique (draft/published).
 */
final class LegalPage
{
    /**
     * @param array<string, mixed> $content Delta Quill décodé ({"ops":[...]})
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
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
        return new self(
            (int) $row['id'],
            (string) $row['title'],
            (string) $row['slug'],
            is_array($content) ? $content : ['ops' => []],
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
