<?php

declare(strict_types=1);

namespace App\Modules\Stats;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Statistiques — mesure d'audience légère du site public, RGPD-clean
 * (sans cookie ni service tiers, IP/UA jamais stockés en clair). Voir
 * docs/modules/stats.md.
 *
 * Collecte : déclenchée en bord de requête depuis le front controller du site
 * public (StatsCollector::record), pour les pages statiques ET dynamiques.
 * Aucune route front : le module n'expose qu'un tableau de bord d'admin.
 *
 * RGPD : empreinte visiteur à sel quotidien (pas de suivi inter-journalier),
 * référent réduit à l'hôte, purge automatique au-delà du délai de conservation.
 */
final class StatsModule implements Module
{
    public function name(): string
    {
        return 'stats';
    }

    public function boot(Router $router): void
    {
        $router->get('/admin/stats', [StatsController::class, 'index']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Statistiques', 'url' => '/admin/stats', 'permission' => 'stats.view'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_page_views.sql',
        ];
    }

    /**
     * Série quotidienne du mois EN COURS (du 1er à aujourd'hui), jours sans vue
     * à 0. Logique partagée par le tableau de bord Stats et le widget de
     * l'accueil de l'admin.
     *
     * @return array<int, array{date: string, label: string, views: int, uniques: int}>
     */
    public static function currentMonthSeries(?StatsRepository $repo = null): array
    {
        $repo ??= new StatsRepository();
        $byDay = $repo->viewsByDay(date('Y-m-01'));
        $today = (int) date('j');
        $series = [];
        for ($day = 1; $day <= $today; $day++) {
            $date = date('Y-m-') . sprintf('%02d', $day);
            $series[] = [
                'date'    => $date,
                'label'   => (string) $day,
                'views'   => $byDay[$date]['views'] ?? 0,
                'uniques' => $byDay[$date]['uniques'] ?? 0,
            ];
        }
        return $series;
    }

    /** Libellé « mois année » en français (sans dépendre de la locale serveur). */
    public static function frenchMonthLabel(): string
    {
        $months = [
            1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
        ];
        return ($months[(int) date('n')] ?? '') . ' ' . date('Y');
    }
}
