<?php

declare(strict_types=1);

namespace App\Modules\Stats;

use App\Core\Config;
use Throwable;

/**
 * Collecte d'une vue de page — appelée en bord de requête par le front
 * controller du site public (frontend/public/index.php), pour CHAQUE page
 * servie (statique ou dynamique). Server-side, sans pixel ni JavaScript.
 *
 * Garanties RGPD (exemption « mesure d'audience » CNIL) :
 *   - aucun cookie n'est posé ni lu pour identifier le visiteur ;
 *   - l'IP et le User-Agent ne sont JAMAIS stockés en clair : ils n'entrent que
 *     dans un hash non réversible à sel quotidien (cf. visitorHash) ;
 *   - on ne stocke que l'hôte du référent, jamais l'URL complète.
 *
 * La collecte est « best-effort » : enveloppée dans un try/catch, elle ne doit
 * jamais casser l'affichage du site (une stat ratée vaut mieux qu'une page KO).
 */
final class StatsCollector
{
    /** Extensions de fichiers à ne jamais compter (un 404 d'asset peut atteindre index.php). */
    private const ASSET_EXTENSIONS = [
        'css', 'js', 'map', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'pdf', 'zip', 'mp4', 'webm', 'xml', 'txt', 'json',
    ];

    /**
     * Enregistre la vue de page pour le chemin demandé ('' = accueil).
     * Toute condition d'exclusion (module inactif, bot, admin, asset, non-GET)
     * provoque un retour silencieux.
     */
    public static function record(string $path): void
    {
        try {
            if (!self::enabled()) {
                return;
            }
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                return; // on ne compte que les consultations, pas les POST de formulaire.
            }

            $path = self::normalizePath($path);
            if ($path === null) {
                return; // asset / extension de fichier : hors mesure d'audience.
            }

            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            if (self::isBot($ua)) {
                return; // robot déclaré : exclu (best-effort, cf. doc).
            }
            if (self::isExcludedVisitor()) {
                return; // membre connecté à l'admin ou IP de l'agence : exclu.
            }

            (new StatsRepository())->record(
                $path,
                self::visitorHash($ua),
                self::refererHost(),
                self::device($ua),
            );
        } catch (Throwable) {
            // silencieux : la mesure d'audience ne doit jamais perturber le site.
        }
    }

    /** Le module Stats est-il activé pour ce site ? */
    private static function enabled(): bool
    {
        $enabled = (array) Config::get('modules.enabled', []);
        return in_array('Stats', $enabled, true);
    }

    /**
     * Normalise le chemin en clé de page stable : '/' pour l'accueil, sinon
     * '/slug' borné à 255 caractères. Renvoie null pour un asset (extension de
     * fichier connue) afin de l'écarter de la mesure.
     */
    private static function normalizePath(string $path): ?string
    {
        $path = '/' . trim($path, '/');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, self::ASSET_EXTENSIONS, true)) {
            return null;
        }
        return mb_substr($path, 0, 255);
    }

    /**
     * Empreinte non réversible du visiteur pour le dédoublonnage du jour.
     * sel quotidien (date du jour) → l'empreinte change chaque jour : aucun
     * suivi dans le temps, aucun profil reconstituable. Secret serveur :
     * APP_SECRET si défini, sinon dérivé de la config DB (comme le module Contact).
     */
    private static function visitorHash(string $ua): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $secret = (string) Config::env('APP_SECRET', '');
        if ($secret === '') {
            $secret = (string) Config::env('DB_NAME', '') . '|' . (string) Config::env('DB_HOST', '');
        }
        $dailySalt = date('Y-m-d');
        return hash('sha256', $ip . '|' . $ua . '|' . $dailySalt . '|' . $secret);
    }

    /**
     * Hôte du référent (sans le query-string, ni l'URL complète), uniquement
     * s'il est externe au site lui-même. null pour un accès direct ou interne.
     */
    private static function refererHost(): ?string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            return null;
        }
        $host = parse_url($referer, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }
        $host = strtolower($host);
        // Référent interne (même hôte que le site) → considéré comme navigation directe.
        $selfHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($selfHost !== '' && $host === $selfHost) {
            return null;
        }
        return mb_substr($host, 0, 255);
    }

    /** Type d'appareil déduit du User-Agent (sans service tiers, heuristique simple). */
    private static function device(string $ua): string
    {
        if ($ua === '') {
            return 'other';
        }
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|Opera Mini/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/Macintosh|Windows NT|X11|Linux|CrOS/i', $ua)) {
            return 'desktop';
        }
        return 'other';
    }

    /** User-Agent de robot déclaré (filtre best-effort, motifs surchargés en config). */
    private static function isBot(string $ua): bool
    {
        if ($ua === '') {
            return true; // pas de User-Agent : quasi toujours un script/robot.
        }
        $patterns = (array) Config::get('stats.bot_user_agents', []);
        foreach ($patterns as $pattern) {
            if (is_string($pattern) && $pattern !== '' && stripos($ua, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Le visiteur doit-il être exclu (membre de l'équipe / agence) ?
     *
     *  - admin connecté : on DÉTECTE la simple présence du cookie de session de
     *    l'admin (lecture de $_COOKIE, sans démarrer de session ni poser de
     *    cookie) — suffisant pour ne pas compter un membre qui navigue sur le
     *    site public en étant loggé ;
     *  - IP de l'agence/bureau : comparée sous forme hachée à stats.exclude_ips.
     */
    private static function isExcludedVisitor(): bool
    {
        if ((bool) Config::get('stats.exclude_admins', true)) {
            $sessionName = (string) Config::get('app.session_name', 'flow_session');
            if (isset($_COOKIE[$sessionName])) {
                return true;
            }
        }

        $excluded = (array) Config::get('stats.exclude_ips', []);
        if ($excluded !== []) {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            if ($ip !== '' && in_array($ip, $excluded, true)) {
                return true;
            }
        }

        return false;
    }
}
