# Module Users

## Rôle

Gère les comptes du back-office et l'authentification : connexion/déconnexion,
sessions sécurisées, anti-brute-force, et CRUD des utilisateurs. Module
**mandataire** : `App\Core\Auth` s'appuie sur son `UserRepository` et son entité
`User` — Flow ne peut pas authentifier sans lui.

## Données

Migrations dans [`app/Modules/Users/migrations/`](../../app/Modules/Users/migrations/).

- **`users`** — source de vérité des comptes.
  `id`, `email` (unique), `password_hash`, `name`, `role`, `created_at`.
  Pas de table `roles`/`permissions` : le champ `role` + le mapping
  [`config/roles.php`](../../config/roles.php) suffisent (brief §7).
- **`login_attempts`** — anti-brute-force (brief §6).
  `id`, `identifier` (e-mail), `ip`, `attempted_at`. Lockout calculé sur une
  fenêtre glissante ; purgé au succès.
- **`password_resets`** — table posée pour le reset par mail (flux non encore branché).
  `id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`.

## Routes

Déclarées dans `UsersModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/login`         | `AuthController::showLogin` | — |
| POST | `/admin/login`         | `AuthController::login`     | CSRF |
| POST | `/admin/logout`        | `AuthController::logout`    | CSRF |
| GET  | `/admin/users`         | `UserController::index`     | `users.manage` |
| GET  | `/admin/users/create`  | `UserController::create`    | `users.manage` |
| POST | `/admin/users/create`  | `UserController::store`     | `users.manage` + CSRF |
| GET  | `/admin/users/edit`    | `UserController::edit`      | `users.manage` |
| POST | `/admin/users/edit`    | `UserController::update`    | `users.manage` + CSRF |
| POST | `/admin/users/delete`  | `UserController::destroy`   | `users.manage` + CSRF |

> Le routeur est minimal (matching exact) : l'`id` cible des actions edit/update/delete
> passe en paramètre de requête (`?id=`) / champ POST, pas en segment d'URL. À
> migrer vers `/admin/users/{id}` quand le routeur gérera les params dynamiques.

## Blocs fournis

Aucun.

## Permissions

| Permission | Détenue par (config/roles.php) |
|------------|--------------------------------|
| `users.manage` | `admin` (`*`), `manager` |

`editor` ne l'a pas → accès refusé (403) à la gestion des comptes.

## Config

- [`config/roles.php`](../../config/roles.php) — mapping rôle → permissions (wildcards `*`, `module.*`).
- [`config/app.php`](../../config/app.php) — `session_name`, paramètres cookie,
  `login_max_attempts`, `login_lockout_window`.
- [`config/modules.php`](../../config/modules.php) — `Users` doit être dans `available` (et il est non désactivable depuis l'onglet Administration).

## Notes / pièges

- **Couplage Core → module assumé** : `App\Core\Auth` référence `UserRepository`/`User`.
  Users est de fait obligatoire ; le désactiver casserait l'auth.
- **Hachage** : `Auth::hashAlgo()` = Argon2id si dispo, sinon `PASSWORD_DEFAULT`.
  Re-hash transparent au login via `password_needs_rehash()`.
- **Garde-fou suppression** : on ne peut pas supprimer son propre compte.
- **Seed** : `php bin/flow user:create <email> <password> [role] [name]`.
- **À venir** : reset de mot de passe par mail (table prête), 2FA TOTP (brief §6),
  passage des routes à params dynamiques.
