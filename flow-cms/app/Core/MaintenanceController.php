<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Onglet Maintenance (`/admin/maintenance`) — réservé à `admin.maintenance`.
 *
 * Active/désactive le mode maintenance du site public, gère l'URL de bypass
 * (jeton régénérable) et la liste des IP autorisées à contourner la maintenance.
 * L'état vit dans storage/maintenance.json (voir App\Core\MaintenanceSettings).
 *
 * Niveau Core et non module : ce garde-fou pilote la disponibilité du site, il
 * ne doit pas pouvoir se désactiver lui-même via le catalogue des modules.
 * Seul le rôle `admin` détient `admin.maintenance` (wildcard `*`).
 */
final class MaintenanceController extends Controller
{
    private MaintenanceSettings $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new MaintenanceSettings(Kernel::instance()->basePath());
    }

    public function index(): void
    {
        $this->requirePermission('admin.maintenance');

        $this->renderPage([__DIR__ . '/views'], 'maintenance', 'Maintenance', [
            'enabled'    => $this->settings->isEnabled(),
            'bypassUrl'  => $this->bypassUrl(),
            'ips'        => $this->settings->allowedIps(),
            'clientIp'   => $this->clientIp(),
            'notice'     => $this->input('notice'),
            'error'      => $this->input('error'),
        ]);
    }

    public function toggle(): void
    {
        $this->requirePermission('admin.maintenance');
        $this->requireCsrf();

        $enable = ($_POST['enabled'] ?? '') === '1';
        $this->settings->setEnabled($enable);

        $notice = $enable ? 'Mode maintenance activé : le site public est fermé.' : 'Mode maintenance désactivé : le site est de nouveau en ligne.';
        $this->redirect('/admin/maintenance?notice=' . rawurlencode($notice));
    }

    public function regenerate(): void
    {
        $this->requirePermission('admin.maintenance');
        $this->requireCsrf();

        $this->settings->regenerateToken();
        $this->redirect('/admin/maintenance?notice=' . rawurlencode('Nouvelle URL de bypass générée. L\'ancienne ne fonctionne plus.'));
    }

    public function addIp(): void
    {
        $this->requirePermission('admin.maintenance');
        $this->requireCsrf();

        $ip = $this->input('ip');
        if ($this->settings->addIp($ip)) {
            $this->redirect('/admin/maintenance?notice=' . rawurlencode('Adresse IP ajoutée : ' . $ip));
        }
        $this->redirect('/admin/maintenance?error=' . rawurlencode('Adresse IP invalide : ' . $ip));
    }

    public function removeIp(): void
    {
        $this->requirePermission('admin.maintenance');
        $this->requireCsrf();

        $ip = $this->input('ip');
        $this->settings->removeIp($ip);
        $this->redirect('/admin/maintenance?notice=' . rawurlencode('Adresse IP retirée : ' . $ip));
    }

    /**
     * URL absolue de bypass à communiquer : base d'URL du site public +
     * paramètre portant le jeton. Visitée, elle inscrit l'IP et ouvre l'accès.
     */
    private function bypassUrl(): string
    {
        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $publicBase = rtrim((string) Config::get('app.public_base_path', ''), '/');
        $query = http_build_query([MaintenanceSettings::BYPASS_PARAM => $this->settings->bypassToken()]);

        return $scheme . '://' . $host . $publicBase . '/?' . $query;
    }
}
