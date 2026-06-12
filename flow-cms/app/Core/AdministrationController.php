<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Onglet Administration (`/admin/administration`) — réservé à `admin.modules`.
 *
 * Permet de choisir quels modules du catalogue (config/modules.php) sont
 * disponibles pour les autres utilisateurs. L'état vit dans storage/modules.json
 * (voir App\Core\ModuleSettings). Niveau Core et non module : cette fonction
 * pilote les modules, elle ne doit pas pouvoir se désactiver elle-même.
 *
 * Seul le rôle `admin` détient `admin.modules` (wildcard `*` dans config/roles.php) ;
 * les rôles manager/editor ne voient pas l'onglet et reçoivent 403 sur la route.
 */
final class AdministrationController extends Controller
{
    private ModuleSettings $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new ModuleSettings(Kernel::instance()->basePath());
    }

    public function index(): void
    {
        $this->requirePermission('admin.modules');

        $enabled = $this->settings->enabled();
        $modules = [];
        foreach ($this->settings->catalog() as $name) {
            $modules[] = [
                'name'        => $name,
                'label'       => $this->settings->label($name),
                'description' => $this->settings->description($name),
                'enabled'     => in_array($name, $enabled, true),
                'protected'   => $this->settings->isProtected($name),
                'requires'    => array_map(fn (string $d): string => $this->settings->label($d), ModuleSettings::DEPENDENCIES[$name] ?? []),
                'dependents'  => array_map(fn (string $d): string => $this->settings->label($d), $this->settings->dependents($name)),
            ];
        }

        $this->renderPage([__DIR__ . '/views'], 'administration', 'Administration', [
            'modules' => $modules,
            'notice'  => $this->input('notice'),
        ]);
    }

    public function saveModules(): void
    {
        $this->requirePermission('admin.modules');
        $this->requireCsrf();

        $requested = array_values(array_filter((array) ($_POST['modules'] ?? []), 'is_string'));
        $cascaded = $this->settings->save($requested);

        $notice = 'Modules enregistrés.';
        if ($cascaded !== []) {
            $labels = array_map(fn (string $n): string => $this->settings->label($n), $cascaded);
            $notice .= ' Désactivés aussi (dépendance manquante) : ' . implode(', ', $labels) . '.';
        }
        $this->redirect('/admin/administration?notice=' . rawurlencode($notice));
    }
}
