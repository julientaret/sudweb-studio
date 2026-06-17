<?php

declare(strict_types=1);

namespace App\Modules\Sliders;

use App\Core\Kernel;
use App\Core\Module;
use App\Core\Router;
use App\Modules\Sliders\Blocks\SliderBlock;

/**
 * Module Sliders — collection de diapositives (titre, description rich text,
 * photo média) affichée sur le front via le bloc dynamique `slider` (brief §3/§4).
 * Réutilise la base commune « contenu » (éditeur de texte du Core) et la
 * bibliothèque Media pour la photo (référencée par ID). Permissions dans l'espace
 * commun « content ». Voir docs/modules/sliders.md.
 *
 * Module indépendant : Media est une option (choix de photo dégradé proprement
 * si désactivé). Aucune route front : l'affichage passe exclusivement par le bloc.
 */
final class SlidersModule implements Module
{
    public function name(): string
    {
        return 'sliders';
    }

    public function boot(Router $router): void
    {
        // Bloc front dynamique : son render() requête la base en live (brief §4).
        Kernel::instance()->blocks()->register(new SliderBlock());

        // Back-office.
        $router->get('/admin/sliders', [SlideController::class, 'index']);
        $router->get('/admin/sliders/create', [SlideController::class, 'create']);
        $router->post('/admin/sliders/create', [SlideController::class, 'store']);
        $router->get('/admin/sliders/edit', [SlideController::class, 'edit']);
        $router->post('/admin/sliders/edit', [SlideController::class, 'update']);
        $router->post('/admin/sliders/delete', [SlideController::class, 'destroy']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Sliders', 'url' => '/admin/sliders', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_slides.sql',
        ];
    }
}
