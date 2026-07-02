<?php

declare(strict_types=1);

namespace App\Modules\Actualites;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Actualités — articles datés avec contenu page-builder (brief §3).
 *
 * Calqué sur le module Agenda : réutilise la base commune « contenu » (blocs
 * partagés enregistrés par Pages via App\Core\BlockContent) et la bibliothèque
 * Media pour la vignette (référencée par ID). Permissions dans l'espace
 * « content » (content.edit, content.publish). Voir docs/modules/actualites.md.
 *
 * Dépendances : modules Pages (blocs + éditeur JS) et Media (vignette) activés.
 */
final class ActualitesModule implements Module
{
    public function name(): string
    {
        return 'actualites';
    }

    public function boot(Router $router): void
    {
        // Back-office.
        $router->get('/admin/actualites', [ArticleController::class, 'index']);
        $router->get('/admin/actualites/create', [ArticleController::class, 'create']);
        $router->post('/admin/actualites/create', [ArticleController::class, 'store']);
        $router->get('/admin/actualites/edit', [ArticleController::class, 'edit']);
        $router->post('/admin/actualites/edit', [ArticleController::class, 'update']);
        $router->post('/admin/actualites/delete', [ArticleController::class, 'destroy']);

        // Front : /actualites/{slug} (deux segments → pas de conflit avec le
        // catch-all /{slug} des Pages, qui ne matche qu'un seul segment).
        $router->get('/actualites/{slug}', [ArticleController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Actualités', 'url' => '/admin/actualites', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_articles.sql',
            __DIR__ . '/migrations/002_add_is_demo.sql',
        ];
    }
}
