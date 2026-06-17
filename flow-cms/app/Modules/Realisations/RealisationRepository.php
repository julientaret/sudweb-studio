<?php

declare(strict_types=1);

namespace App\Modules\Realisations;

use App\Core\Database;
use PDO;

/**
 * Accès aux réalisations. Requêtes préparées (brief §6).
 */
final class RealisationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?Realisation
    {
        $stmt = $this->pdo->prepare('SELECT * FROM realisations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Realisation::fromRow($row) : null;
    }

    /**
     * Liste d'admin : tout, ordonnée par position puis id.
     *
     * @return array<int, Realisation>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM realisations ORDER BY position ASC, id DESC')->fetchAll();
        return array_map(static fn (array $r): Realisation => Realisation::fromRow($r), $rows);
    }

    /**
     * Réalisations publiées pour le front (bloc dynamique), limitées.
     *
     * @return array<int, Realisation>
     */
    public function published(int $limit): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM realisations WHERE status = 'published'
             ORDER BY position ASC, id DESC LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(static fn (array $r): Realisation => Realisation::fromRow($r), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $description Delta Quill ({ops:[...]})
     */
    public function create(
        string $title,
        array $description,
        ?int $mediaId,
        int $position,
        string $status,
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO realisations (title, description, media_id, position, status, created_at, updated_at)
             VALUES (:title, :description, :media, :position, :status, :created, :updated)'
        );
        $stmt->execute([
            'title'       => $title,
            'description' => json_encode($description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'media'       => $mediaId,
            'position'    => $position,
            'status'      => $status,
            'created'     => $now,
            'updated'     => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $description Delta Quill ({ops:[...]})
     */
    public function update(
        int $id,
        string $title,
        array $description,
        ?int $mediaId,
        int $position,
        string $status,
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE realisations SET title = :title, description = :description, media_id = :media,
                                     position = :position, status = :status, updated_at = :now
             WHERE id = :id'
        );
        $stmt->execute([
            'title'       => $title,
            'description' => json_encode($description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'media'       => $mediaId,
            'position'    => $position,
            'status'      => $status,
            'now'         => date('Y-m-d H:i:s'),
            'id'          => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM realisations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
