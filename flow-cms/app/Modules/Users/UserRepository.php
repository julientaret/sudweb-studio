<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Database;
use PDO;

/**
 * Accès aux comptes (brief §3 : le module possède ses données).
 *
 * Toutes les requêtes sont préparées (brief §6). Source de vérité : table `users`.
 */
final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    public function find(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    /**
     * @return array<int, User>
     */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
        return array_map(static fn (array $row): User => User::fromRow($row), $rows);
    }

    /**
     * Crée un compte. Renvoie l'ID inséré.
     */
    public function create(string $email, string $passwordHash, string $name, string $role): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, name, role, created_at)
             VALUES (:email, :hash, :name, :role, :created_at)'
        );
        $stmt->execute([
            'email'      => strtolower(trim($email)),
            'hash'       => $passwordHash,
            'name'       => $name,
            'role'       => $role,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour email / nom / rôle (et le mot de passe si fourni).
     */
    public function update(int $id, string $email, string $name, string $role, ?string $passwordHash = null): void
    {
        if ($passwordHash !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE users SET email = :email, name = :name, role = :role, password_hash = :hash
                 WHERE id = :id'
            );
            $stmt->execute([
                'email' => strtolower(trim($email)),
                'name'  => $name,
                'role'  => $role,
                'hash'  => $passwordHash,
                'id'    => $id,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users SET email = :email, name = :name, role = :role WHERE id = :id'
        );
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'name'  => $name,
            'role'  => $role,
            'id'    => $id,
        ]);
    }

    /**
     * Enregistre les préférences du tableau de bord (JSON : ordre + cartes masquées).
     */
    public function updateDashboardPrefs(int $id, string $json): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET dashboard_prefs = :prefs WHERE id = :id');
        $stmt->execute(['prefs' => $json, 'id' => $id]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
