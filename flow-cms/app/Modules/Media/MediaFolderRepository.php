<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Database;
use PDO;

/**
 * Accès aux dossiers de la médiathèque. Requêtes préparées (brief §6).
 */
final class MediaFolderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Tous les dossiers, triés par nom, avec leur nombre de médias.
     *
     * @return array<int, MediaFolder>
     */
    public function all(): array
    {
        $sql = 'SELECT f.*, COUNT(m.id) AS count
                FROM media_folders f
                LEFT JOIN media m ON m.folder_id = f.id
                GROUP BY f.id
                ORDER BY f.name ASC';
        $rows = $this->pdo->query($sql)->fetchAll();
        return array_map(static fn (array $r): MediaFolder => MediaFolder::fromRow($r), $rows);
    }

    public function find(int $id): ?MediaFolder
    {
        $stmt = $this->pdo->prepare('SELECT * FROM media_folders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? MediaFolder::fromRow($row) : null;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM media_folders WHERE name = :name';
        $params = ['name' => $name];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $name): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO media_folders (name, created_at) VALUES (:name, :now)'
        );
        $stmt->execute(['name' => $name, 'now' => date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    public function rename(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE media_folders SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);
    }

    /**
     * Supprime le dossier ; ses médias repassent en « Non classé » (folder_id NULL).
     */
    public function delete(int $id): void
    {
        $this->pdo->prepare('UPDATE media SET folder_id = NULL WHERE folder_id = :id')
            ->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM media_folders WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /** Nombre de médias « Non classés » (sans dossier). */
    public function countUnfiled(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM media WHERE folder_id IS NULL')->fetchColumn();
    }
}
