<?php

declare(strict_types=1);

namespace App\Modules\Realisations;

use App\Core\Kernel;
use App\Core\Module;
use App\Core\Router;
use App\Modules\Realisations\Blocks\RealisationsBlock;

/**
 * Module Réalisations — collection de réalisations (titre, description rich text,
 * photo média) affichée sur le front via le bloc dynamique `realisations`
 * (brief §3/§4). Réutilise la base commune « contenu » (éditeur de texte du Core)
 * et la bibliothèque Media pour la photo (référencée par ID). Permissions dans
 * l'espace commun « content ». Voir docs/modules/realisations.md.
 *
 * Module indépendant : Media est une option (choix de photo dégradé proprement
 * si désactivé). Aucune route front : l'affichage passe exclusivement par le bloc.
 */
final class RealisationsModule implements Module
{
    public function name(): string
    {
        return 'realisations';
    }

    public function boot(Router $router): void
    {
        // Bloc front dynamique : son render() requête la base en live (brief §4).
        Kernel::instance()->blocks()->register(new RealisationsBlock());

        // Back-office.
        $router->get('/admin/realisations', [RealisationController::class, 'index']);
        $router->get('/admin/realisations/create', [RealisationController::class, 'create']);
        $router->post('/admin/realisations/create', [RealisationController::class, 'store']);
        $router->get('/admin/realisations/edit', [RealisationController::class, 'edit']);
        $router->post('/admin/realisations/edit', [RealisationController::class, 'update']);
        $router->post('/admin/realisations/delete', [RealisationController::class, 'destroy']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Réalisations', 'url' => '/admin/realisations', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_realisations.sql',
            __DIR__ . '/migrations/002_add_is_demo.sql',
        ];
    }
}
