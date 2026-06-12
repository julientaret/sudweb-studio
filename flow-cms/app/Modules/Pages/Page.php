<?php

declare(strict_types=1);

namespace App\Modules\Pages;

/**
 * Entité page (brief §4).
 *
 * `content` est le tableau ordonné de blocs décodé depuis le JSON :
 * chaque bloc = ['id' => string, 'type' => string, 'data' => array].
 */
final class Page
{
    /**
     * @param array<int, array{id:string,type:string,data:array<string,mixed>}> $content
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
