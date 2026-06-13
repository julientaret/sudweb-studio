<?php

declare(strict_types=1);

namespace App\Modules\Stats;

use App\Core\Database;
use PDO;

/**
 * Accès aux vues de pages (mesure d'audience). Requêtes préparées partout
 * (brief §6). Les méthodes d'agrégat alimentent le tableau de bord ; aucune ne
 * renvoie de donnée identifiante (on ne lit jamais le visitor_hash en clair, on
 * ne fait que le COMPTER en distinct).
 */
final class StatsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * Enregistre une vue de page. Appelé en bord de requête par StatsCollector ;
     * volontairement minimal (un seul INSERT).
     */
    public function record(string $path, string $visitorHash, ?string $refererHost, string $device): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO page_views (path, visitor_hash, referer_host, device, viewed_at, viewed_on)
             VALUES (:path, :visitor, :referer, :device, :at, :on)'
        );
        $now = time();
        $stmt->execute([
            'path'    => $path,
            'visitor' => $visitorHash,
            'referer' => $refererHost,
            'device'  => $device,
            'at'      => date('Y-m-d H:i:s', $now),
            'on'      => date('Y-m-d', $now),
        ]);
    }

    /** Nombre total de vues depuis une date (incluse). */
    public function totalViews(string $since): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM page_views WHERE viewed_on >= :since');
        $stmt->execute(['since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Visiteurs uniques sur la période. Le sel du hash changeant chaque jour,
     * c'est le CUMUL des visiteurs uniques quotidiens (exact sur une seule
     * journée, somme des uniques/jour sur une période — métrique privacy-first
     * assumée, sans suivi inter-journalier).
     */
    public function uniqueVisitors(string $since): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE viewed_on >= :since');
        $stmt->execute(['since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Vues et visiteurs uniques par jour depuis une date.
     *
     * @return array<string, array{views: int, uniques: int}> indexé par date Y-m-d
     */
    public function viewsByDay(string $since): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT viewed_on, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS uniques
             FROM page_views WHERE viewed_on >= :since
             GROUP BY viewed_on ORDER BY viewed_on'
        );
        $stmt->execute(['since' => $since]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['viewed_on']] = [
                'views'   => (int) $row['views'],
                'uniques' => (int) $row['uniques'],
            ];
        }
        return $out;
    }

    /**
     * Pages les plus vues sur la période.
     *
     * @return array<int, array{path: string, views: int}>
     */
    public function topPages(string $since, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT path, COUNT(*) AS views FROM page_views WHERE viewed_on >= :since
             GROUP BY path ORDER BY views DESC, path LIMIT :limit'
        );
        $stmt->bindValue('since', $since);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(
            static fn (array $r): array => ['path' => (string) $r['path'], 'views' => (int) $r['views']],
            $stmt->fetchAll()
        );
    }

    /**
     * Sources de trafic (hôtes référents) sur la période, hors accès directs.
     *
     * @return array<int, array{host: string, views: int}>
     */
    public function topReferers(string $since, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT referer_host, COUNT(*) AS views FROM page_views
             WHERE viewed_on >= :since AND referer_host IS NOT NULL AND referer_host <> ''
             GROUP BY referer_host ORDER BY views DESC, referer_host LIMIT :limit"
        );
        $stmt->bindValue('since', $since);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(
            static fn (array $r): array => ['host' => (string) $r['referer_host'], 'views' => (int) $r['views']],
            $stmt->fetchAll()
        );
    }

    /**
     * Répartition par type d'appareil sur la période.
     *
     * @return array<string, int> device => vues
     */
    public function deviceBreakdown(string $since): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT device, COUNT(*) AS views FROM page_views WHERE viewed_on >= :since
             GROUP BY device ORDER BY views DESC'
        );
        $stmt->execute(['since' => $since]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['device']] = (int) $row['views'];
        }
        return $out;
    }

    /**
     * RGPD — purge des vues plus anciennes qu'une date. Retourne le nombre de
     * lignes supprimées.
     */
    public function purgeOlderThan(string $cutoff): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM page_views WHERE viewed_on < :cutoff');
        $stmt->execute(['cutoff' => $cutoff]);
        return $stmt->rowCount();
    }
}
