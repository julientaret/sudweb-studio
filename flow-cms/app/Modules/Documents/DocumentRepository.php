<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Database;
use PDO;

/**
 * Accès au référentiel documents. Requêtes préparées (brief §6).
 */
final class DocumentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?Document
    {
        $stmt = $this->pdo->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Document::fromRow($row) : null;
    }

    /**
     * @return array<int, Document>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM documents ORDER BY created_at DESC, id DESC')->fetchAll();
        return array_map(static fn (array $r): Document => Document::fromRow($r), $rows);
    }

    /**
     * @param array{filename:string,original_name:string,title?:string,mime:string,ext:string,size:int,folder_id?:?int} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO documents (filename, original_name, title, mime, ext, size, folder_id, created_at)
             VALUES (:filename, :original_name, :title, :mime, :ext, :size, :folder_id, :created_at)'
        );
        $stmt->execute([
            'filename'      => $data['filename'],
            'original_name' => $data['original_name'],
            'title'         => $data['title'] ?? '',
            'mime'          => $data['mime'],
            'ext'           => $data['ext'],
            'size'          => $data['size'],
            'folder_id'     => $data['folder_id'] ?? null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Documents d'un dossier donné (null = « Non classé »).
     *
     * @return array<int, Document>
     */
    public function allInFolder(?int $folderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE folder_id <=> :fid ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['fid' => $folderId]);
        return array_map(static fn (array $r): Document => Document::fromRow($r), $stmt->fetchAll());
    }

    /** Déplace un document vers un dossier (null = « Non classé »). */
    public function move(int $id, ?int $folderId): void
    {
        $stmt = $this->pdo->prepare('UPDATE documents SET folder_id = :fid WHERE id = :id');
        $stmt->execute(['fid' => $folderId, 'id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
    }

    public function updateTitle(int $id, string $title): void
    {
        $stmt = $this->pdo->prepare('UPDATE documents SET title = :title WHERE id = :id');
        $stmt->execute(['title' => $title, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM documents WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
