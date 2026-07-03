<?php

declare(strict_types=1);

namespace App\Modules\Horaires;

use App\Core\Kernel;
use App\Core\Module;
use App\Core\Router;
use App\Modules\Horaires\Blocks\OpeningHoursBlock;

/**
 * Module Horaires d'ouverture — jours de la semaine + fermetures exceptionnelles
 * (congés, jours fériés…), affichés sur le site via le bloc `horaires`.
 *
 * État stocké dans storage/horaires.json (App\Modules\Horaires\HorairesSettings) :
 * pas de table dédiée, une donnée d'admin simple ne le justifie pas (même choix
 * que le mode démo ou Avis Google pour son établissement suivi).
 */
final class HorairesModule implements Module
{
    public function name(): string
    {
        return 'horaires';
    }

    public function boot(Router $router): void
    {
        // Bloc front dynamique : son render() lit HorairesSettings en live.
        Kernel::instance()->blocks()->register(new OpeningHoursBlock());

        // Back-office.
        $router->get('/admin/horaires', [HorairesController::class, 'index']);
        $router->post('/admin/horaires/weekly', [HorairesController::class, 'saveWeekly']);
        $router->post('/admin/horaires/exceptions', [HorairesController::class, 'saveExceptions']);

        // Aucune route front : l'affichage passe exclusivement par le bloc.
    }

    public function adminMenu(): array
    {
        return [
            ['label' => "Horaires d'ouverture", 'url' => '/admin/horaires', 'permission' => 'horaires.view'],
        ];
    }

    public function migrations(): array
    {
        return [];
    }
}
