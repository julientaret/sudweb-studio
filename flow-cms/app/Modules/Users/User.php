<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Config;

/**
 * Entité utilisateur (brief §7).
 *
 * Porte l'identité du compte et la résolution des permissions. Règle d'or :
 * le reste du code teste une permission via can(), jamais le rôle directement.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $name,
        public readonly string $role,
        public readonly string $createdAt,
        public readonly ?string $dashboardPrefs = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row ligne SQL de la table users
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['name'],
            (string) $row['role'],
            (string) $row['created_at'],
            isset($row['dashboard_prefs']) ? (string) $row['dashboard_prefs'] : null,
        );
    }

    /**
     * L'utilisateur détient-il la permission demandée ?
     *
     * Résolution via le mapping config/roles.php, avec wildcards :
     *  - '*'        → toutes les permissions ;
     *  - 'media.*'  → toutes les actions du module media ;
     *  - 'blog.edit'→ exacte.
     */
    public function can(string $permission): bool
    {
        $granted = Config::get('roles.' . $this->role, []);
        if (!is_array($granted)) {
            return false;
        }

        foreach ($granted as $rule) {
            if ($this->matches((string) $rule, $permission)) {
                return true;
            }
        }
        return false;
    }

    private function matches(string $rule, string $permission): bool
    {
        if ($rule === '*' || $rule === $permission) {
            return true;
        }
        // Wildcard de module : "media.*" couvre "media.upload", "media.delete"…
        if (str_ends_with($rule, '.*')) {
            $prefix = substr($rule, 0, -1); // garde le point final : "media."
            return str_starts_with($permission, $prefix);
        }
        return false;
    }
}
