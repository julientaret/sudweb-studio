<?php

declare(strict_types=1);

namespace App\Modules\Devis;

use App\Core\Database;
use PDO;

/**
 * Accès aux demandes de devis. Requêtes préparées partout (brief §6).
 */
final class DevisRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?QuoteRequest
    {
        $stmt = $this->pdo->prepare('SELECT * FROM quote_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? QuoteRequest::fromRow($row) : null;
    }

    /**
     * @return array<int, QuoteRequest>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM quote_requests ORDER BY created_at DESC, id DESC')->fetchAll();
        return array_map(static fn (array $r): QuoteRequest => QuoteRequest::fromRow($r), $rows);
    }

    /**
     * Nombre de demandes non lues (badge du menu admin).
     */
    public function countNew(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn();
    }

    /**
     * Nombre d'envois d'une même IP (hachée) depuis une date — anti-abus.
     */
    public function countByIpSince(string $ipHash, string $since): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM quote_requests WHERE ip_hash = :ip AND created_at > :since'
        );
        $stmt->execute(['ip' => $ipHash, 'since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Persiste une demande. `$answersJson` est la liste ordonnée [{label,value}]
     * des réponses aux questions dynamiques (hors nom/e-mail, qui ont leur colonne).
     */
    public function create(
        string $name,
        string $email,
        string $answersJson,
        bool $consent,
        string $consentVersion,
        ?string $ipHash,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO quote_requests
                (name, email, answers, consent, consent_version, ip_hash, status, created_at)
             VALUES
                (:name, :email, :answers, :consent, :version, :ip, :status, :created)'
        );
        $stmt->execute([
            'name'    => $name,
            'email'   => $email,
            'answers' => $answersJson,
            'consent' => $consent ? 1 : 0,
            'version' => $consentVersion,
            'ip'      => $ipHash,
            'status'  => 'new',
            'created' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Change le statut (new|read|quoted|archived).
     */
    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE quote_requests SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM quote_requests WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * RGPD — purge des demandes plus anciennes qu'une date. Retourne le nombre
     * de lignes supprimées.
     */
    public function purgeOlderThan(string $cutoff): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM quote_requests WHERE created_at < :cutoff');
        $stmt->execute(['cutoff' => $cutoff]);
        return $stmt->rowCount();
    }
}
