<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews;

use App\Core\Kernel;
use App\Core\Module;
use App\Core\Router;
use App\Modules\GoogleReviews\Blocks\ReviewsBlock;

/**
 * Module Avis Google — import des avis de l'établissement (brief §3).
 *
 * Synchro à la demande (clic bouton dans l'admin) : à chaque sync on récupère
 * les 5 avis les plus récents via l'API Google Places (legacy,
 * reviews_sort=newest) et on les empile en base avec déduplication. La liste
 * d'admin permet de choisir, avis par avis, lesquels sont affichés sur le front
 * via le bloc dynamique `google_reviews`. Voir docs/modules/google-reviews.md.
 *
 * Config requise : GOOGLE_PLACES_API_KEY dans .env.local (lue via
 * config/google_reviews.php). L'établissement se choisit dans l'admin
 * (recherche Find Place), stocké dans storage/google_reviews.json.
 */
final class GoogleReviewsModule implements Module
{
    public function name(): string
    {
        return 'google_reviews';
    }

    public function boot(Router $router): void
    {
        // Bloc front dynamique : son render() requête la base en live (brief §4).
        Kernel::instance()->blocks()->register(new ReviewsBlock());

        // Back-office.
        $router->get('/admin/google-reviews', [GoogleReviewsController::class, 'index']);
        $router->post('/admin/google-reviews/place', [GoogleReviewsController::class, 'selectPlace']);
        $router->post('/admin/google-reviews/sync', [GoogleReviewsController::class, 'sync']);
        $router->post('/admin/google-reviews/toggle', [GoogleReviewsController::class, 'toggle']);
        $router->post('/admin/google-reviews/purge', [GoogleReviewsController::class, 'purge']);

        // Aucune route front : l'affichage passe exclusivement par le bloc.
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Avis Google', 'url' => '/admin/google-reviews', 'permission' => 'googlereviews.view'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_google_reviews.sql',
        ];
    }
}
