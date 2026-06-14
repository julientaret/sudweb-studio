<?php

declare(strict_types=1);

namespace App\Core;

use App\Modules\Users\LoginIpRepository;
use App\Modules\Users\SecurityAlert;
use App\Modules\Users\User;
use App\Modules\Users\UserRepository;
use PDO;

/**
 * Authentification (brief §6 & §7).
 *
 * Mécanisme dans le Core (session, vérification du mot de passe, anti-brute-force) ;
 * le stockage des comptes appartient au module Users (UserRepository + entité User),
 * qui est de fait un module mandataire — voir docs/modules/users.md.
 *
 * Suppose une session déjà démarrée (front controller).
 */
final class Auth
{
    private const SESSION_KEY = 'auth_user_id';

    private UserRepository $users;

    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
    }

    /**
     * Tente une connexion. Renvoie l'utilisateur en cas de succès, sinon null.
     *
     * - lockout temporaire progressif après trop d'échecs (compte + IP) ;
     * - password_verify() ; re-hash transparent si le coût a évolué ;
     * - session_regenerate_id(true) au succès (anti-fixation).
     */
    public function attempt(string $email, string $password, string $ip): ?User
    {
        $email = strtolower(trim($email));

        if ($this->isLockedOut($email, $ip)) {
            return null;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user->passwordHash)) {
            $this->recordAttempt($email, $ip);
            return null;
        }

        // Re-hash si le coût/algorithme par défaut a changé (brief §6).
        if (password_needs_rehash($user->passwordHash, self::hashAlgo())) {
            $newHash = password_hash($password, self::hashAlgo());
            $this->users->updatePasswordHash($user->id, $newHash);
        }

        $this->clearAttempts($email, $ip);
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $user->id;

        $this->guardLoginIp($user, $ip);

        return $user;
    }

    /**
     * Sécurité (brief §6) : enregistre l'IP de connexion et, si elle est
     * inhabituelle pour ce compte (au moins une IP déjà connue, mais pas
     * celle-ci), pose un drapeau de session pour le bandeau d'alerte de l'admin
     * et notifie le support par e-mail.
     *
     * Best-effort : ne doit jamais empêcher une connexion légitime (table absente
     * avant migration, e-mail indisponible…).
     */
    private function guardLoginIp(User $user, string $ip): void
    {
        try {
            $ips = new LoginIpRepository();
            $hadKnown = $ips->hasAny($user->id);
            $isKnown = $ips->isKnown($user->id, $ip);
            $ips->record($user->id, $ip);

            // Première connexion (aucune IP connue) : on enregistre sans alerter.
            if ($hadKnown && !$isKnown) {
                $_SESSION[SecurityController::IP_ALERT_KEY] = ['ip' => $ip, 'at' => date('Y-m-d H:i:s')];
                SecurityAlert::notifyNewIp($user, $ip);
            }
        } catch (\Throwable) {
            // Surveillance best-effort : on n'interrompt jamais la connexion.
        }
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    /**
     * Utilisateur connecté, ou null. Mis en cache pour la requête courante.
     */
    public function user(): ?User
    {
        static $cached = false;
        static $value = null;

        if ($cached) {
            return $value;
        }

        $cached = true;
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if ($id === null) {
            return $value = null;
        }
        return $value = $this->users->find((int) $id);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Teste une permission (jamais un rôle — brief §7).
     */
    public function can(string $permission): bool
    {
        $user = $this->user();
        return $user !== null && $user->can($permission);
    }

    /**
     * Algorithme de hachage : Argon2id si disponible, sinon le défaut PHP (brief §6).
     */
    public static function hashAlgo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    // --- Anti-brute-force -------------------------------------------------

    private function isLockedOut(string $email, string $ip): bool
    {
        $window = (int) Config::get('app.login_lockout_window', 900); // 15 min
        $max = (int) Config::get('app.login_max_attempts', 5);

        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (identifier = :id OR ip = :ip) AND attempted_at > :since'
        );
        $stmt->execute([
            'id'    => $email,
            'ip'    => $ip,
            'since' => date('Y-m-d H:i:s', time() - $window),
        ]);

        return (int) $stmt->fetchColumn() >= $max;
    }

    private function recordAttempt(string $email, string $ip): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO login_attempts (identifier, ip, attempted_at) VALUES (:id, :ip, :at)'
        );
        $stmt->execute([
            'id' => $email,
            'ip' => $ip,
            'at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function clearAttempts(string $email, string $ip): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM login_attempts WHERE identifier = :id OR ip = :ip'
        );
        $stmt->execute(['id' => $email, 'ip' => $ip]);
    }
}
