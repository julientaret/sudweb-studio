<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews;

use App\Core\Database;
use PDO;

/**
 * Accès aux avis Google importés. Requêtes préparées (brief §6).
 */
final class GoogleReviewsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Insère ou met à jour un lot d'avis bruts venant de l'API.
     *
     * Dédup sur l'empreinte (auteur + timestamp). Le flag is_visible d'un avis
     * déjà connu n'est JAMAIS écrasé : la curation faite par l'admin est
     * préservée d'une synchro à l'autre.
     *
     * @param array<int, array<string, mixed>> $reviews
     * @return int Nombre de nouveaux avis insérés.
     */
    public function upsertMany(array $reviews): int
    {
        $sql = <<<SQL
            INSERT INTO google_reviews
                (fingerprint, author_name, author_url, profile_photo_url,
                 rating, text, language, relative_time, review_time, is_visible, synced_at)
            VALUES
                (:fingerprint, :author_name, :author_url, :profile_photo_url,
                 :rating, :text, :language, :relative_time, :review_time, 1, NOW())
            ON DUPLICATE KEY UPDATE
                rating        = VALUES(rating),
                text          = VALUES(text),
                relative_time = VALUES(relative_time),
                review_time   = VALUES(review_time),
                synced_at     = VALUES(synced_at)
            SQL;

        $stmt     = $this->pdo->prepare($sql);
        $inserted = 0;

        foreach ($reviews as $r) {
            $author = (string) ($r['author_name'] ?? 'Anonyme');
            $time   = (int) ($r['time'] ?? 0);

            $stmt->execute([
                'fingerprint'       => sha1($author . '#' . $time),
                'author_name'       => $author,
                'author_url'        => $r['author_url'] ?? null,
                'profile_photo_url' => $r['profile_photo_url'] ?? null,
                'rating'            => (int) ($r['rating'] ?? 0),
                'text'              => $r['text'] ?? null,
                'language'          => $r['language'] ?? null,
                'relative_time'     => $r['relative_time_description'] ?? null,
                'review_time'       => $time,
            ]);

            // MySQL : rowCount() == 1 => INSERT, == 2 => UPDATE, == 0 => inchangé.
            if ($stmt->rowCount() === 1) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /** @return array<int, array<string, mixed>> Tous les avis, pour la liste d'admin. */
    public function allForAdmin(): array
    {
        return $this->pdo
            ->query('SELECT * FROM google_reviews ORDER BY review_time DESC')
            ->fetchAll();
    }

    /** @return array<int, array<string, mixed>> Avis visibles, pour le bloc front. */
    public function visible(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM google_reviews WHERE is_visible = 1 ORDER BY review_time DESC LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function setVisibility(int $id, bool $visible): void
    {
        $stmt = $this->pdo->prepare('UPDATE google_reviews SET is_visible = :v WHERE id = :id');
        $stmt->execute(['v' => $visible ? 1 : 0, 'id' => $id]);
    }

    /** Vide la table (bouton « Vider la table » de l'admin). */
    public function deleteAll(): void
    {
        $this->pdo->exec('DELETE FROM google_reviews');
    }

    public function lastSyncedAt(): ?string
    {
        $val = $this->pdo->query('SELECT MAX(synced_at) FROM google_reviews')->fetchColumn();

        return $val !== false && $val !== null ? (string) $val : null;
    }
}
