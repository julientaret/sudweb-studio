<?php

declare(strict_types=1);

namespace App\Modules\Horaires;

use App\Core\Controller;

/**
 * Horaires d'ouverture — back-office. Deux formulaires distincts sur un même
 * écran (comme GoogleReviews « établissement » / « quota ») :
 *   - saveWeekly()     : les 7 jours de la semaine (fermé, ou plage horaire) ;
 *   - saveExceptions() : la liste des fermetures ponctuelles (congés, jours
 *     fériés…), resoumise en entier à chaque sauvegarde — liste assez courte
 *     pour ne pas justifier des routes de suppression unitaire.
 *
 * Permissions dans l'espace « horaires » : view (consultation) / manage
 * (édition), détenues par admin (wildcard) et manager (config/roles.php).
 */
final class HorairesController extends Controller
{
    private HorairesSettings $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new HorairesSettings();
    }

    /** @return array<int, string> */
    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    public function index(): void
    {
        $this->requirePermission('horaires.view');

        $this->renderPage($this->views(), 'edit', "Horaires d'ouverture", [
            'weekly'     => $this->settings->weekly(),
            'dayLabels'  => HorairesSettings::DAY_LABELS,
            'days'       => HorairesSettings::DAYS,
            'exceptions' => $this->settings->exceptions(),
            'canManage'  => $this->auth->can('horaires.manage'),
            'notice'     => $this->input('notice'),
        ]);
    }

    public function saveWeekly(): void
    {
        $this->requirePermission('horaires.manage');
        $this->requireCsrf();

        $days = $_POST['days'] ?? [];
        $this->settings->setWeekly(is_array($days) ? $days : []);

        $this->redirect('/admin/horaires?notice=' . rawurlencode('Horaires hebdomadaires enregistrés.'));
    }

    public function saveExceptions(): void
    {
        $this->requirePermission('horaires.manage');
        $this->requireCsrf();

        $exceptions = $_POST['exceptions'] ?? [];
        $this->settings->setExceptions(is_array($exceptions) ? $exceptions : []);

        $this->redirect('/admin/horaires?notice=' . rawurlencode('Fermetures exceptionnelles enregistrées.'));
    }
}
