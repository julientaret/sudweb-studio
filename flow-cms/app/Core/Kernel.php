<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Noyau de Flow : orchestre le démarrage (brief §2, point d'entrée unique).
 *
 * Charge la config, prépare la session et les en-têtes de sécurité, instancie le
 * routeur et le registre de modules, puis démarre les modules. Utilisé par le
 * front controller (web) et par bin/flow (CLI, sans session ni headers).
 */
final class Kernel
{
    private static ?Kernel $instance = null;

    private string $basePath;
    private Router $router;
    private ModuleRegistry $registry;
    private BlockRegistry $blocks;

    private function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        Config::boot($this->basePath);

        // Chemin de base URL : auto-détecté pour fonctionner aussi bien à la racine
        // d'un vhost que dans un sous-dossier (ex. /flow-cms). Surchargé si la config
        // le fixe explicitement.
        $urlBase = $this->resolveUrlBasePath();
        Config::set('app.base_path', $urlBase);

        // Le site public (frontend) est un dossier voisin du CMS. Les fichiers
        // uploadés vivent dans frontend/public (servis avec le site public), et
        // sont référencés à la base d'URL du site — la MÊME depuis l'admin et
        // depuis le front, pour que les médias d'une page ne cassent jamais.
        //
        //  - storage.public_dir    : dossier servi du frontend (chemin disque) ;
        //  - app.public_base_path  : base d'URL du site public. Côté front c'est
        //    app.base_path ; côté admin, c'est app.base_path sans le segment du
        //    dossier CMS (ex. /flow/flow-cms → /flow).
        Config::set('storage.public_dir', dirname($this->basePath) . '/frontend/public');
        $cmsDir = basename($this->basePath);
        $publicBase = preg_replace('#/' . preg_quote($cmsDir, '#') . '$#', '', $urlBase);
        Config::set('app.public_base_path', $publicBase ?? $urlBase);

        $this->router = new Router($urlBase);
        $this->blocks = new BlockRegistry();

        // Blocs de contenu de base (niveau Core) : toujours disponibles pour tout
        // module qui utilise le page-builder, indépendamment des modules activés.
        // Les blocs additionnels (image → Media, avis → GoogleReviews) sont
        // enregistrés par leur module dans boot().
        $this->blocks->register(new \App\Core\Blocks\HeadingBlock());
        $this->blocks->register(new \App\Core\Blocks\RichtextBlock());

        // Modules effectifs : catalogue (config/modules.php) filtré par l'état
        // choisi dans l'admin (storage/modules.json, onglet Administration). La
        // liste est publiée dans la config pour le code qui teste 'modules.enabled'.
        $enabled = (new ModuleSettings($this->basePath))->enabled();
        Config::set('modules.enabled', $enabled);
        $this->registry = new ModuleRegistry($enabled);
    }

    /**
     * Déduit le préfixe d'URL de l'application.
     *
     * Priorité à la valeur de config si elle est une chaîne non vide ; sinon
     * détection depuis SCRIPT_NAME (web uniquement) en retirant un éventuel
     * segment /public final.
     */
    private function resolveUrlBasePath(): string
    {
        $configured = Config::get('app.base_path', null);
        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }
        if (PHP_SAPI === 'cli') {
            return '';
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        // Site public : le front controller vit dans frontend/public, mais le site
        // est servi à la racine du projet (ex. /flow). On retire le segment entier.
        $dir = preg_replace('#/frontend/public$#', '', $dir) ?? $dir;
        // Admin : front controller dans public/ (ex. /flow/flow-cms/public → /flow/flow-cms).
        $dir = preg_replace('#/public$#', '', $dir) ?? $dir;
        return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
    }

    public static function boot(string $basePath): self
    {
        return self::$instance ??= new self($basePath);
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Kernel non démarré.');
        }
        return self::$instance;
    }

    /**
     * Démarre une requête web : session, en-têtes de sécurité, boot des modules.
     */
    public function handleWeb(): void
    {
        $this->startSession();
        $this->sendSecurityHeaders();

        // Filtrage géographique : tout le back-office est réservé aux pays
        // autorisés (France par défaut). Fail-open si l'infra ne fournit pas le
        // pays — voir App\Core\IpGeoGuard et config/security.php.
        IpGeoGuard::enforce();

        // Entrée par défaut : la racine mène à l'outil. Un module front (Pages)
        // pourra surcharger '/' en l'enregistrant dans son boot().
        $base = (string) Config::get('app.base_path', '');
        $this->router->get('/', function () use ($base): void {
            $dest = (new Auth())->check() ? '/admin' : '/admin/login';
            header('Location: ' . $base . $dest);
        });

        // Accueil de l'admin : tableau de bord (shell, niveau Core). Route exacte,
        // donc prioritaire sur le catch-all front /{slug} des Pages.
        $this->router->get('/admin', [DashboardController::class, 'index']);
        $this->router->post('/admin/dashboard/layout', [DashboardController::class, 'saveLayout']);
        $this->router->get('/admin/tutoriel', [TutorialController::class, 'index']);

        // Sécurité — acquittement du bandeau d'alerte « connexion IP inhabituelle ».
        $this->router->post('/admin/security/ip-alert/dismiss', [SecurityController::class, 'dismissIpAlert']);

        // Administration (choix des modules) — niveau Core, réservé à admin.modules.
        $this->router->get('/admin/administration', [AdministrationController::class, 'index']);
        $this->router->post('/admin/administration/modules', [AdministrationController::class, 'saveModules']);

        // Maintenance (fermeture du site public + bypass) — niveau Core, réservé
        // à admin.maintenance. Voir App\Core\MaintenanceSettings.
        $this->router->get('/admin/maintenance', [MaintenanceController::class, 'index']);
        $this->router->post('/admin/maintenance/toggle', [MaintenanceController::class, 'toggle']);
        $this->router->post('/admin/maintenance/regenerate', [MaintenanceController::class, 'regenerate']);
        $this->router->post('/admin/maintenance/ip/add', [MaintenanceController::class, 'addIp']);
        $this->router->post('/admin/maintenance/ip/remove', [MaintenanceController::class, 'removeIp']);

        $this->registry->bootAll($this->router);
        $this->router->dispatch(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    }

    /**
     * Démarre une requête web côté site public (frontend).
     *
     * Même noyau, mais surface réduite : pas de routes d'admin du Core, pas de
     * session (le front est sans état). On boot les modules — ce qui enregistre
     * les blocs et le catch-all front /{slug} des Pages — puis on dispatche.
     * La racine '/' rend la page d'accueil (slug app.home_slug) si le module
     * Pages est présent.
     */
    public function handleFront(): void
    {
        $this->sendSecurityHeaders();

        $homeSlug = (string) Config::get('app.home_slug', 'accueil');
        $this->router->get('/', static function () use ($homeSlug): void {
            if (class_exists(\App\Modules\Pages\PageController::class)) {
                (new \App\Modules\Pages\PageController())->show(['slug' => $homeSlug]);
                return;
            }
            http_response_code(404);
            echo '<!doctype html><meta charset="utf-8"><title>404</title><h1>404</h1>';
        });

        $this->registry->bootAll($this->router);
        $this->router->dispatch(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function registry(): ModuleRegistry
    {
        return $this->registry;
    }

    /** Registre des blocs de page (les modules y enregistrent leurs blocs au boot). */
    public function blocks(): BlockRegistry
    {
        return $this->blocks;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        session_set_cookie_params([
            'lifetime' => (int) Config::get('app.session_lifetime', 0),
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name((string) Config::get('app.session_name', 'flow_session'));
        session_start();
    }

    public function sendSecurityHeaders(): void
    {
        // brief §6 — posés dans le front controller.
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'");

        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
