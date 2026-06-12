<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Page tutoriel de l'admin (`/admin/tutoriel`).
 *
 * Documentation pas-à-pas, intégrée à l'outil, des différentes parties du CMS
 * (tableau de bord, pages, agenda, médias, utilisateurs). Niveau Core : transverse
 * aux modules, accessible à tout utilisateur connecté.
 *
 * Les rubriques liées à un module ne s'affichent que si ce module est actif :
 * la liste effective (catalogue − désactivés dans l'admin) est passée à la vue,
 * qui filtre ses sections en conséquence.
 */
final class TutorialController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->renderPage([__DIR__ . '/views'], 'tutorial', 'Tutoriel', [
            'enabledModules' => (array) Config::get('modules.enabled', []),
        ]);
    }
}
