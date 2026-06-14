<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Database;
use PDO;

/**
 * Adresses IP de connexion connues par compte. Requêtes préparées partout
 * (brief §6). Source de vérité : table `user_login_ips`.
 */
final class LoginIpRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** Le compte a-t-il déjà au moins une IP de connexion enregistrée ? */
    public function hasAny(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM user_login_ips WHERE user_id = :u LIMIT 1');
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchColumn() !== false;
    }

    /** Cette IP est-elle déjà connue pour ce compte ? */
    public function isKnown(int $userId, string $ip): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM user_login_ips WHERE user_id = :u AND ip = :ip LIMIT 1');
        $stmt->execute(['u' => $userId, 'ip' => $ip]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Enregistre l'IP pour ce compte (ou rafraîchit sa date de dernière vue).
     */
    public function record(int $userId, string $ip): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_login_ips (user_id, ip, first_seen, last_seen)
             VALUES (:u, :ip, :first, :last)
             ON DUPLICATE KEY UPDATE last_seen = :touch'
        );
        $stmt->execute([
            'u'     => $userId,
            'ip'    => $ip,
            'first' => $now,
            'last'  => $now,
            'touch' => $now,
        ]);
    }
}
