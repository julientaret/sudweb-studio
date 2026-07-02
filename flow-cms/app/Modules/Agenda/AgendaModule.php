<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Agenda — événements datés avec contenu page-builder (brief §3).
 *
 * Réutilise la base commune « contenu » : le champ contenu est composé avec les
 * blocs partagés (enregistrés par le module Pages) via App\Core\BlockContent, et
 * l'image vignette référence la bibliothèque Media par ID. Permissions dans
 * l'espace « content » (content.edit, content.publish). Voir docs/modules/agenda.md.
 *
 * Dépendances : modules Pages (blocs + éditeur JS) et Media (vignette) activés.
 */
final class AgendaModule implements Module
{
    public function name(): string
    {
        return 'agenda';
    }

    public function boot(Router $router): void
    {
        // Back-office.
        $router->get('/admin/agenda', [EventController::class, 'index']);
        $router->get('/admin/agenda/create', [EventController::class, 'create']);
        $router->post('/admin/agenda/create', [EventController::class, 'store']);
        $router->get('/admin/agenda/edit', [EventController::class, 'edit']);
        $router->post('/admin/agenda/edit', [EventController::class, 'update']);
        $router->post('/admin/agenda/delete', [EventController::class, 'destroy']);

        // Front : /agenda/{slug} (deux segments → pas de conflit avec le catch-all
        // /{slug} des Pages, qui ne matche qu'un seul segment).
        $router->get('/agenda/{slug}', [EventController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Agenda', 'url' => '/admin/agenda', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_events.sql',
            __DIR__ . '/migrations/002_add_is_demo.sql',
        ];
    }
}
