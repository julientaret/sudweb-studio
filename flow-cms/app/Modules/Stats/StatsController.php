<?php

declare(strict_types=1);

namespace App\Modules\Stats;

use App\Core\Config;
use App\Core\Controller;

/**
 * Statistiques — tableau de bord d'audience (back-office). Lecture seule : le
 * module ne fait qu'agréger et présenter les vues collectées par StatsCollector.
 */
final class StatsController extends Controller
{
    /** Périodes proposées dans le sélecteur (jours). */
    private const PERIODS = [7, 30, 90, 365];

    private StatsRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new StatsRepository();
    }

    public function index(): void
    {
        $this->requirePermission('stats.view');
        $this->purgeExpired(); // RGPD : nettoyage opportuniste à l'ouverture.

        $days = (int) $this->input('days', '30');
        if (!in_array($days, self::PERIODS, true)) {
            $days = 30;
        }

        // Borne basse incluse : 'days' jours calendaires en remontant jusqu'à aujourd'hui.
        $since = date('Y-m-d', strtotime('-' . ($days - 1) . ' days') ?: time());

        $this->renderPage([__DIR__ . '/views'], 'dashboard', 'Statistiques', [
            'days'           => $days,
            'periods'        => self::PERIODS,
            'totalViews'     => $this->repo->totalViews($since),
            'uniqueVisitors' => $this->repo->uniqueVisitors($since),
            'daily'          => $this->fillDailySeries($this->repo->viewsByDay($since), $days),
            'month'          => $this->currentMonthSeries(),
            'monthLabel'     => $this->frenchMonthLabel(),
            'topPages'       => $this->repo->topPages($since, 10),
            'topReferers'    => $this->repo->topReferers($since, 10),
            'devices'        => $this->repo->deviceBreakdown($since),
            'retention'      => (int) Config::get('stats.retention_months', 13),
        ]);
    }

    /**
     * Complète la série quotidienne pour couvrir CHAQUE jour de la période
     * (les jours sans vue valent 0), afin d'avoir un graphe régulier.
     *
     * @param array<string, array{views: int, uniques: int}> $byDay
     * @return array<int, array{date: string, label: string, views: int, uniques: int}>
     */
    private function fillDailySeries(array $byDay, int $days): array
    {
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime('-' . $i . ' days') ?: time();
            $date = date('Y-m-d', $ts);
            $series[] = [
                'date'    => $date,
                'label'   => date('d/m', $ts),
                'views'   => $byDay[$date]['views'] ?? 0,
                'uniques' => $byDay[$date]['uniques'] ?? 0,
            ];
        }
        return $series;
    }

    /**
     * Série quotidienne du mois EN COURS (du 1er à aujourd'hui), pour le petit
     * graphique mensuel. Les jours sans vue valent 0.
     *
     * @return array<int, array{date: string, label: string, views: int, uniques: int}>
     */
    private function currentMonthSeries(): array
    {
        $byDay = $this->repo->viewsByDay(date('Y-m-01'));
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
    private function frenchMonthLabel(): string
    {
        $months = [
            1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
        ];
        return ($months[(int) date('n')] ?? '') . ' ' . date('Y');
    }

    /**
     * RGPD : supprime les vues au-delà du délai de conservation
     * (config/stats.php → retention_months ; 0 = purge désactivée).
     */
    private function purgeExpired(): void
    {
        $months = (int) Config::get('stats.retention_months', 0);
        if ($months <= 0) {
            return;
        }
        $cutoff = date('Y-m-d', strtotime('-' . $months . ' months') ?: time());
        $this->repo->purgeOlderThan($cutoff);
    }
}
