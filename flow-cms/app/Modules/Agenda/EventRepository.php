<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use App\Core\Database;
use PDO;

/**
 * Accès aux événements de l'agenda. Requêtes préparées (brief §6).
 */
final class EventRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?Event
    {
        $stmt = $this->pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Event::fromRow($row) : null;
    }

    public function findPublishedBySlug(string $slug): ?Event
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM events WHERE slug = :slug AND status = 'published' LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ? Event::fromRow($row) : null;
    }

    /**
     * @return array<int, Event>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM events ORDER BY start_date DESC, id DESC')->fetchAll();
        return array_map(static fn (array $r): Event => Event::fromRow($r), $rows);
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM events WHERE slug = :slug';
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
    public function create(
        string $title,
        string $slug,
        string $startDate,
        ?string $endDate,
        ?int $thumbnailId,
        array $content,
        string $status,
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO events (title, slug, start_date, end_date, thumbnail_id, content, status, created_at, updated_at)
             VALUES (:title, :slug, :start, :end, :thumb, :content, :status, :created, :updated)'
        );
        $stmt->execute([
            'title'   => $title,
            'slug'    => $slug,
            'start'   => $startDate,
            'end'     => $endDate,
            'thumb'   => $thumbnailId,
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
    public function update(
        int $id,
        string $title,
        string $slug,
        string $startDate,
        ?string $endDate,
        ?int $thumbnailId,
        array $content,
        string $status,
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE events SET title = :title, slug = :slug, start_date = :start, end_date = :end,
                               thumbnail_id = :thumb, content = :content, status = :status, updated_at = :now
             WHERE id = :id'
        );
        $stmt->execute([
            'title'   => $title,
            'slug'    => $slug,
            'start'   => $startDate,
            'end'     => $endDate,
            'thumb'   => $thumbnailId,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'  => $status,
            'now'     => date('Y-m-d H:i:s'),
            'id'      => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM events WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
