<?php

declare(strict_types=1);

namespace App\Modules\Pages;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Pages — page-builder par blocs (brief §4).
 *
 * Module indépendant : il consomme le page-builder partagé (blocs de base fournis
 * par le Core, blocs additionnels par leur module) mais ne dépend d'aucun autre
 * module. Permissions dans l'espace « content » (base commune aux modules de
 * contenu, brief §3/§7) : content.edit, content.publish. Voir docs/modules/pages.md.
 */
final class PagesModule implements Module
{
    public function name(): string
    {
        return 'pages';
    }

    public function boot(Router $router): void
    {
        // Back-office.
        $router->get('/admin/pages', [PageController::class, 'index']);
        $router->get('/admin/pages/create', [PageController::class, 'create']);
        $router->post('/admin/pages/create', [PageController::class, 'store']);
        $router->get('/admin/pages/edit', [PageController::class, 'edit']);
        $router->post('/admin/pages/edit', [PageController::class, 'update']);
        $router->post('/admin/pages/delete', [PageController::class, 'destroy']);

        // Front : /{slug} (les routes exactes admin restent prioritaires).
        $router->get('/{slug}', [PageController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Pages', 'url' => '/admin/pages', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_pages.sql',
            __DIR__ . '/migrations/002_add_is_demo.sql',
        ];
    }
}
