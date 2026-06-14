<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Users — comptes, connexion, rôles (brief §7).
 *
 * Module mandataire : le Core\Auth s'appuie sur son UserRepository et son entité
 * User. Voir docs/modules/users.md.
 */
final class UsersModule implements Module
{
    public function name(): string
    {
        return 'users';
    }

    public function boot(Router $router): void
    {
        // Authentification.
        $router->get('/admin/login', [AuthController::class, 'showLogin']);
        $router->post('/admin/login', [AuthController::class, 'login']);
        $router->post('/admin/logout', [AuthController::class, 'logout']);

        // Gestion des comptes (protégée par users.manage dans le contrôleur).
        $router->get('/admin/users', [UserController::class, 'index']);
        $router->get('/admin/users/create', [UserController::class, 'create']);
        $router->post('/admin/users/create', [UserController::class, 'store']);
        $router->get('/admin/users/edit', [UserController::class, 'edit']);
        $router->post('/admin/users/edit', [UserController::class, 'update']);
        $router->post('/admin/users/delete', [UserController::class, 'destroy']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Utilisateurs', 'url' => '/admin/users', 'permission' => 'users.manage'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_users.sql',
            __DIR__ . '/migrations/002_create_login_attempts.sql',
            __DIR__ . '/migrations/003_create_password_resets.sql',
            __DIR__ . '/migrations/004_add_dashboard_prefs.sql',
            __DIR__ . '/migrations/005_create_user_login_ips.sql',
        ];
    }
}
