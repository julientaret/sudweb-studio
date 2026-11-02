<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Helpers du site public (frontend). Inclus via require_once par les partials et
 * les pages statiques. Complète les helpers du CMS (e(), slug()…) déjà autoloadés.
 */

if (!function_exists('site')) {
    /**
     * Accès aux paramètres du site (frontend/config/site.php), chargés une fois.
     *
     * site()        => tout le tableau de config
     * site('name')  => une valeur, avec défaut optionnel
     */
    function site(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $file = dirname(__DIR__) . '/config/site.php';
            $config = is_file($file) ? (array) require $file : [];
        }
        if ($key === null) {
            return $config;
        }
        return $config[$key] ?? $default;
    }
}

if (!function_exists('site_url')) {
    /**
     * Construit une URL du site : un chemin interne ('/contact') est préfixé par
     * la base d'URL ('/flow/contact') ; une URL absolue (http…, //…) est rendue
     * telle quelle.
     */
    function site_url(string $path = '/'): string
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $path;
        }
        $base = rtrim((string) Config::get('app.base_path', ''), '/');
        return $base . $path;
    }
}

if (!function_exists('asset_url')) {
    /**
     * URL d'un asset statique (frontend/public/...) avec un paramètre `v` basé
     * sur la date de modification du fichier sur disque : chaque rebuild change
     * l'URL, ce qui invalide le cache navigateur sans étape de déploiement
     * supplémentaire (les assets n'ont sinon aucune version dans l'URL).
     */
    function asset_url(string $path): string
    {
        $file = dirname(__DIR__) . '/public' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '';
        $url = site_url($path);
        return $version !== '' ? $url . '?v=' . $version : $url;
    }
}

if (!function_exists('abs_url')) {
    /**
     * URL ABSOLUE d'un chemin du site ('/contact' => 'https://exemple.fr/contact').
     *
     * Les robots des réseaux sociaux ne résolvent pas les chemins relatifs : les
     * métas d'aperçu (og:url, og:image) ont besoin d'une URL complète.
     *
     * Base : APP_URL (app.url), qui contient déjà le préfixe du site. À défaut,
     * on la reconstitue depuis la requête — l'hôte venant du client, il est
     * filtré sur les caractères d'un nom d'hôte avant d'être recopié.
     */
    function abs_url(string $path = '/'): string
    {
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $base = rtrim((string) Config::get('app.url', ''), '/');

        if ($base === '') {
            $host = (string) preg_replace('/[^A-Za-z0-9.\-:]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
            if ($host === '') {
                return site_url($path);   // faute de mieux : chemin relatif
            }
            $https  = ($_SERVER['HTTPS'] ?? 'off') !== 'off'
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
            $base = ($https ? 'https://' : 'http://') . $host
                . rtrim((string) Config::get('app.base_path', ''), '/');
        }

        return $base . $path;
    }
}

if (!function_exists('asset_abs_url')) {
    /**
     * URL absolue d'un asset statique, versionnée comme asset_url().
     *
     * Sert aux images d'aperçu : changer l'image change l'URL, ce qui force les
     * réseaux sociaux à la recharger au lieu de servir leur copie en cache.
     */
    function asset_abs_url(string $path): string
    {
        $file = dirname(__DIR__) . '/public' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '';
        $url = abs_url($path);
        return $version !== '' ? $url . '?v=' . $version : $url;
    }
}

if (!function_exists('current_path')) {
    /**
     * Chemin de la page courante, relatif à la racine du site et normalisé :
     * '/' pour l'accueil, '/realisations' sinon. Sert à marquer l'entrée de
     * navigation active (cf. nav_is_active()).
     */
    function current_path(): string
    {
        $base = rtrim((string) Config::get('app.base_path', ''), '/');
        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        return '/' . trim($path, '/');
    }
}

if (!function_exists('nav_is_active')) {
    /**
     * L'entrée de navigation $url correspond-elle à la page courante $current ?
     *
     * Seules les entrées qui désignent une PAGE sont comparées, sur leur chemin.
     * Une entrée vers une ancre ('/#expertises') n'est jamais active au rendu :
     * elle ne vaut que pour une portion de page, et c'est le scroll-spy de la
     * couche motion (motion.js) qui l'allume quand la section passe à l'écran.
     */
    function nav_is_active(string $url, string $current): bool
    {
        // Ancre : traitée au défilement, pas au rendu.
        if (str_contains($url, '#')) {
            return false;
        }
        // Lien externe : jamais actif.
        if (!str_starts_with($url, '/')) {
            return false;
        }
        return '/' . trim($url, '/') === $current;
    }
}
