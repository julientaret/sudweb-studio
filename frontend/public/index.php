<?php

declare(strict_types=1);

/**
 * Front controller unique du site public (frontend).
 *
 * Résolution d'une requête, dans cet ordre de priorité :
 *   1. redirections déclarées dans frontend/config/static_routes.php (301/302) ;
 *   2. pages statiques écrites à la main — fichiers PHP dans frontend/static/
 *      (ex. /flow/         → static/index.php,
 *           /flow/contact  → static/contact.php), avec alias éventuels déclarés
 *      dans static_routes.php (URL ≠ nom de fichier) ;
 *   3. pages dynamiques du CMS — résolues par slug via le noyau (App\Core\Kernel),
 *      qui reste l'unique source de vérité pour le routage et le rendu des blocs.
 *
 * Les fichiers statiques l'emportent : un fichier explicite gagne toujours sur
 * une page DB de même URL.
 */

use App\Core\Config;
use App\Core\Kernel;

// Racine du CMS (dossier voisin) : autoload PSR-4 + config + modules y vivent.
$cmsRoot = dirname(__DIR__, 2) . '/flow-cms';

require $cmsRoot . '/vendor/autoload.php';

$kernel = Kernel::boot($cmsRoot);

// Thème du site public : ses templates surchargent ceux des modules (front_layout).
Config::set('app.front_views', [dirname(__DIR__) . '/layouts']);

// Slug demandé : chemin relatif à la racine du site, sans slash de bord
// ('' pour l'accueil, 'contact', 'infos/mentions-legales').
$base = rtrim((string) Config::get('app.base_path', ''), '/');
$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
$slug = trim($path, '/');

// Registre des routes du site public (redirections + alias statiques).
$routesFile = dirname(__DIR__) . '/config/static_routes.php';
$config = is_file($routesFile) ? (array) require $routesFile : [];

// --- 1) Redirection déclarée ? ----------------------------------------------

$redirect = ($config['redirects'] ?? [])[$slug] ?? null;
if ($redirect !== null) {
    $to     = is_array($redirect) ? (string) ($redirect['to'] ?? '/') : (string) $redirect;
    $status = is_array($redirect) ? (int) ($redirect['status'] ?? 301) : 301;
    // Destination interne (commence par '/', sans être protocole-relative) :
    // on la préfixe par la base d'URL du site. Une URL absolue passe telle quelle.
    if (str_starts_with($to, '/') && !str_starts_with($to, '//')) {
        $to = $base . $to;
    }
    header('Location: ' . $to, true, in_array($status, [301, 302, 307, 308], true) ? $status : 301);
    return;
}

// --- 2) Page statique ? (alias déclaré, sinon convention <slug>.php) ---------

$target = ($config['routes'] ?? [])[$slug] ?? ($slug === '' ? 'index' : $slug);

$staticDir = dirname(__DIR__) . '/static';
// Cible bornée (lettres, chiffres, -, _, sous-dossiers) : aucun '.' donc pas de
// traversée de répertoire. On revérifie ensuite avec realpath par sécurité.
if (preg_match('#^[a-z0-9][a-z0-9/_-]*$#i', $target)) {
    $file = realpath($staticDir . '/' . $target . '.php');
    $root = realpath($staticDir);
    if ($file !== false && $root !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
        $kernel->sendSecurityHeaders();
        require $file;
        return;
    }
}

// --- 3) Sinon : pages dynamiques du CMS (par slug) --------------------------

$kernel->handleFront();
