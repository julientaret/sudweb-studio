<?php

declare(strict_types=1);

namespace App\Modules\Pages;

use App\Core\Database;
use PDO;

/**
 * Accès aux pages (brief §4). Requêtes préparées (brief §6).
 */
final class PageRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?Page
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Page::fromRow($row) : null;
    }

    public function findPublishedBySlug(string $slug): ?Page
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ? Page::fromRow($row) : null;
    }

    /**
     * @return array<int, Page>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM pages ORDER BY updated_at DESC, id DESC')->fetchAll();
        return array_map(static fn (array $r): Page => Page::fromRow($r), $rows);
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM pages WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array<int, array<string, mixed>> $content
     */
    public function create(string $title, string $slug, array $content, string $status): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO pages (title, slug, content, status, created_at, updated_at)
             VALUES (:title, :slug, :content, :status, :created, :updated)'
        );
        $stmt->execute([
            'title'   => $title,
            'slug'    => $slug,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'  => $status,
            'created' => $now,
            'updated' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<int, array<string, mixed>> $content
     */
    public function update(int $id, string $title, string $slug, array $content, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE pages SET title = :title, slug = :slug, content = :content,
                              status = :status, updated_at = :now
             WHERE id = :id'
        );
        $stmt->execute([
            'title'   => $title,
            'slug'    => $slug,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'  => $status,
            'now'     => date('Y-m-d H:i:s'),
            'id'      => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
