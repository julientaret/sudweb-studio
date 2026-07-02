<?php

declare(strict_types=1);

namespace App\Modules\Sliders;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Sliders — collection de diapositives (titre, description rich text,
 * photo média), gérée en admin (brief §3/§4). Réutilise la base commune
 * « contenu » (éditeur de texte du Core) et la bibliothèque Media pour la photo
 * (référencée par ID). Permissions dans l'espace commun « content ». Voir
 * docs/modules/sliders.md.
 *
 * Module indépendant : Media est une option (choix de photo dégradé proprement
 * si désactivé).
 *
 * Le bloc `slider` (page-builder) n'est plus enregistré (retiré du sélecteur de
 * blocs) : le module n'a donc **aucune route front** pour l'instant — les
 * diapositives restent éditables en admin mais n'apparaissent plus sur le site
 * public tant qu'un autre point d'affichage n'est pas réintroduit.
 */
final class SlidersModule implements Module
{
    public function name(): string
    {
        return 'sliders';
    }

    public function boot(Router $router): void
    {
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
