<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Database;
use PDO;

/**
 * Accès aux dossiers de la bibliothèque de documents. Requêtes préparées (brief §6).
 */
final class DocumentFolderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Tous les dossiers, triés par nom, avec leur nombre de documents.
     *
     * @return array<int, DocumentFolder>
     */
    public function all(): array
    {
        $sql = 'SELECT f.*, COUNT(d.id) AS count
                FROM document_folders f
                LEFT JOIN documents d ON d.folder_id = f.id
                GROUP BY f.id
                ORDER BY f.name ASC';
        $rows = $this->pdo->query($sql)->fetchAll();
        return array_map(static fn (array $r): DocumentFolder => DocumentFolder::fromRow($r), $rows);
    }

    public function find(int $id): ?DocumentFolder
    {
        $stmt = $this->pdo->prepare('SELECT * FROM document_folders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? DocumentFolder::fromRow($row) : null;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM document_folders WHERE name = :name';
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
            'INSERT INTO document_folders (name, created_at) VALUES (:name, :now)'
        );
        $stmt->execute(['name' => $name, 'now' => date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    public function rename(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE document_folders SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);
    }

    /**
     * Supprime le dossier ; ses documents repassent en « Non classé » (folder_id NULL).
     */
    public function delete(int $id): void
    {
        $this->pdo->prepare('UPDATE documents SET folder_id = NULL WHERE folder_id = :id')
            ->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM document_folders WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /** Nombre de documents « Non classés » (sans dossier). */
    public function countUnfiled(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM documents WHERE folder_id IS NULL')->fetchColumn();
    }
}
