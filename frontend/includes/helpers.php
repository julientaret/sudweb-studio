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
