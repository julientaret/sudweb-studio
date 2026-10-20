<?php

declare(strict_types=1);

namespace App\Modules\Contact;

use App\Core\Database;
use PDO;

/**
 * Accès aux demandes de contact. Requêtes préparées partout (brief §6).
 */
final class ContactRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function find(int $id): ?ContactSubmission
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contact_submissions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ContactSubmission::fromRow($row) : null;
    }

    /**
     * @return array<int, ContactSubmission>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM contact_submissions ORDER BY created_at DESC, id DESC')->fetchAll();
        return array_map(static fn (array $r): ContactSubmission => ContactSubmission::fromRow($r), $rows);
    }

    /**
     * Nombre de demandes non lues (badge du menu admin).
     */
    public function countNew(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'new'")->fetchColumn();
    }

    /**
     * Nombre d'envois d'une même IP (hachée) depuis une date — anti-abus.
     */
    public function countByIpSince(string $ipHash, string $since): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM contact_submissions WHERE ip_hash = :ip AND created_at > :since'
        );
        $stmt->execute(['ip' => $ipHash, 'since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    /** Nombre total de demandes depuis $since, toutes IP confondues. */
    public function countSince(string $since): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM contact_submissions WHERE created_at > :since'
        );
        $stmt->execute(['since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    public function create(
        string $name,
        string $email,
        string $subject,
        string $message,
        bool $consent,
        string $consentVersion,
        ?string $ipHash,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contact_submissions
                (name, email, subject, message, consent, consent_version, ip_hash, status, created_at)
             VALUES (:name, :email, :subject, :message, :consent, :version, :ip, :status, :created)'
        );
        $stmt->execute([
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message,
            'consent' => $consent ? 1 : 0,
            'version' => $consentVersion,
            'ip'      => $ipHash,
            'status'  => 'new',
            'created' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Change le statut (new|read|archived).
     */
    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE contact_submissions SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM contact_submissions WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * RGPD — purge des demandes plus anciennes qu'une date. Retourne le nombre
     * de lignes supprimées.
     */
    public function purgeOlderThan(string $cutoff): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM contact_submissions WHERE created_at < :cutoff');
        $stmt->execute(['cutoff' => $cutoff]);
        return $stmt->rowCount();
    }
}
