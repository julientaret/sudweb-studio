<?php

declare(strict_types=1);

namespace App\Modules\LegalPages;

use App\Core\Database;
use PDO;

/**
 * Accès aux pages légales. Requêtes préparées (brief §6).
 */
final class LegalPageRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?LegalPage
    {
        $stmt = $this->pdo->prepare('SELECT * FROM legal_pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? LegalPage::fromRow($row) : null;
    }

    public function findPublishedBySlug(string $slug): ?LegalPage
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM legal_pages WHERE slug = :slug AND status = 'published' LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ? LegalPage::fromRow($row) : null;
    }

    /**
     * @return array<int, LegalPage>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM legal_pages ORDER BY title ASC')->fetchAll();
        return array_map(static fn (array $r): LegalPage => LegalPage::fromRow($r), $rows);
    }

    /**
     * Slugs des pages publiées — sert à enregistrer les routes front exactes.
     *
     * @return array<int, string>
     */
    public function publishedSlugs(): array
    {
        $rows = $this->pdo->query("SELECT slug FROM legal_pages WHERE status = 'published'")->fetchAll();
        return array_map(static fn (array $r): string => (string) $r['slug'], $rows);
    }

    /**
     * Pages publiées sous forme de liens (titre + slug), pour le pied de page
     * du site public. Ordonnées par titre.
     *
     * @return array<int, array{title: string, slug: string}>
     */
    public function publishedLinks(): array
    {
        $rows = $this->pdo->query(
            "SELECT title, slug FROM legal_pages WHERE status = 'published' ORDER BY title ASC"
        )->fetchAll();
        return array_map(
            static fn (array $r): array => ['title' => (string) $r['title'], 'slug' => (string) $r['slug']],
            $rows
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM legal_pages WHERE slug = :slug';
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
     * @param array<string, mixed> $content Delta Quill ({"ops":[...]})
     */
    public function create(string $title, string $slug, array $content, string $status): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO legal_pages (title, slug, content, status, created_at, updated_at)
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
     * @param array<string, mixed> $content Delta Quill ({"ops":[...]})
     */
    public function update(int $id, string $title, string $slug, array $content, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE legal_pages SET title = :title, slug = :slug, content = :content,
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
        $stmt = $this->pdo->prepare('DELETE FROM legal_pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
