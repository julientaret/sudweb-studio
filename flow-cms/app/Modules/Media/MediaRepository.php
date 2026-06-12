<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Database;
use PDO;

/**
 * Accès au référentiel media (brief §5). Requêtes préparées (brief §6).
 */
final class MediaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?Media
    {
        $stmt = $this->pdo->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Media::fromRow($row) : null;
    }

    /**
     * Récupère plusieurs médias par ID, en préservant l'ordre demandé et en
     * ignorant les ID introuvables.
     *
     * @param array<int, int> $ids
     * @return array<int, Media>
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $i): bool => $i > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM media WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        $byId = [];
        foreach ($stmt->fetchAll() as $row) {
            $media = Media::fromRow($row);
            $byId[$media->id] = $media;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    /**
     * @return array<int, Media>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM media ORDER BY created_at DESC, id DESC')->fetchAll();
        return array_map(static fn (array $r): Media => Media::fromRow($r), $rows);
    }

    /**
     * @param array{filename:string,original_name:string,mime:string,width:int,height:int,size:int,alt?:string,folder_id?:?int} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO media (filename, original_name, mime, width, height, size, alt, folder_id, created_at)
             VALUES (:filename, :original_name, :mime, :width, :height, :size, :alt, :folder_id, :created_at)'
        );
        $stmt->execute([
            'filename'      => $data['filename'],
            'original_name' => $data['original_name'],
            'mime'          => $data['mime'],
            'width'         => $data['width'],
            'height'        => $data['height'],
            'size'          => $data['size'],
            'alt'           => $data['alt'] ?? '',
            'folder_id'     => $data['folder_id'] ?? null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Médias d'un dossier donné (null = « Non classé »).
     *
     * @return array<int, Media>
     */
    public function allInFolder(?int $folderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM media WHERE folder_id <=> :fid ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['fid' => $folderId]);
        return array_map(static fn (array $r): Media => Media::fromRow($r), $stmt->fetchAll());
    }

    /** Déplace un média vers un dossier (null = « Non classé »). */
    public function move(int $id, ?int $folderId): void
    {
        $stmt = $this->pdo->prepare('UPDATE media SET folder_id = :fid WHERE id = :id');
        $stmt->execute(['fid' => $folderId, 'id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM media')->fetchColumn();
    }

    public function updateAlt(int $id, string $alt): void
    {
        $stmt = $this->pdo->prepare('UPDATE media SET alt = :alt WHERE id = :id');
        $stmt->execute(['alt' => $alt, 'id' => $id]);
    }

    /** Enregistre un recadrage (pixels de l'original). */
    public function updateCrop(int $id, int $x, int $y, int $w, int $h): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE media SET crop_x = :x, crop_y = :y, crop_w = :w, crop_h = :h WHERE id = :id'
        );
        $stmt->execute(['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'id' => $id]);
    }

    /** Réinitialise le recadrage (retour à l'original entier). */
    public function clearCrop(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE media SET crop_x = NULL, crop_y = NULL, crop_w = NULL, crop_h = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM media WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
