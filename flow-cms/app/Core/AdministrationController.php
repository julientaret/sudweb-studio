<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Onglet Administration (`/admin/administration`) — réservé à `admin.modules`.
 *
 * Trois réglages transverses, chacun avec son propre état :
 *  - choix des modules du catalogue (config/modules.php) disponibles pour les
 *    autres utilisateurs, stocké dans storage/modules.json (App\Core\ModuleSettings) ;
 *  - durée par défaut des animations au scroll (AOS), storage/aos.json (App\Core\AosSettings) ;
 *  - mode démonstration (compte + données factices), storage/demo.json
 *    (App\Core\DemoSettings + App\Core\DemoSeeder).
 * Niveau Core et non module : cette fonction pilote les modules, elle ne doit
 * pas pouvoir se désactiver elle-même.
 *
 * Seul le rôle `admin` détient `admin.modules` (wildcard `*` dans config/roles.php) ;
 * les rôles manager/editor ne voient pas l'onglet et reçoivent 403 sur la route.
 */
final class AdministrationController extends Controller
{
    private ModuleSettings $settings;
    private AosSettings $aos;
    private DemoSettings $demoSettings;
    private DemoSeeder $demoSeeder;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new ModuleSettings(Kernel::instance()->basePath());
        $this->aos = new AosSettings(Kernel::instance()->basePath());
        $this->demoSettings = new DemoSettings(Kernel::instance()->basePath());
        $this->demoSeeder = new DemoSeeder($this->demoSettings);
    }

    public function index(): void
    {
        $this->requirePermission('admin.modules');
        $this->renderIndex();
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

    public function saveAos(): void
    {
        $this->requirePermission('admin.modules');
        $this->requireCsrf();

        $this->aos->setDuration((int) $this->input('duration', (string) AosSettings::DEFAULT_DURATION));
        $this->redirect('/admin/administration?notice=' . rawurlencode('Durée des animations enregistrée.'));
    }

    /**
     * Active ou désactive le mode démonstration. À l'activation, le mot de
     * passe généré n'existe qu'en mémoire (jamais persisté) : on rend la page
     * directement plutôt que de rediriger, pour ne jamais le faire transiter
     * par une URL (logs serveur, historique du navigateur).
     */
    public function toggleDemo(): void
    {
        $this->requirePermission('admin.modules');
        $this->requireCsrf();

        $enable = ($_POST['enabled'] ?? '') === '1';
        if ($enable) {
            $password = $this->demoSeeder->activate();
            $this->renderIndex([
                'demoNotice'   => 'Mode démonstration activé.',
                'demoPassword' => $password,
            ]);
            return;
        }

        $this->demoSeeder->deactivate();
        $this->redirect('/admin/administration?notice=' . rawurlencode('Mode démonstration désactivé : les données factices ont été supprimées.'));
    }

    /**
     * @param array<string, mixed> $extra variables de vue additionnelles
     *        (ex. demoPassword, affiché une seule fois par toggleDemo()).
     */
    private function renderIndex(array $extra = []): void
    {
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

        $this->renderPage([__DIR__ . '/views'], 'administration', 'Administration', array_merge([
            'modules'        => $modules,
            'aosDuration'    => $this->aos->duration(),
            'aosDurationOpt' => AosSettings::DURATION_OPTIONS,
            'demoEnabled'    => $this->demoSettings->isEnabled(),
            'demoEmail'      => $this->demoSeeder->accountEmail(),
            'demoPassword'   => null,
            'notice'         => $this->input('notice'),
        ], $extra));
    }
}
