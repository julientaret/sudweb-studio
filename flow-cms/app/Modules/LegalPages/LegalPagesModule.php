<?php

declare(strict_types=1);

namespace App\Modules\LegalPages;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Pages légales — pages juridiques (mentions légales, politique de
 * confidentialité, CGU, cookies…) éditées au texte riche dans l'admin (brief §3).
 *
 * Volontairement plus simple que Pages/Actualités : une seule zone de texte riche
 * par page (pas de page-builder par blocs), car le besoin est de la prose
 * juridique. Le contenu est stocké en Delta Quill (JSON) et rendu côté front par
 * le App\Core\DeltaRenderer partagé (renderer borné du Core, sans dépendance à un
 * autre module).
 *
 * URLs front à la racine (/mentions-legales, /<slug>) : on enregistre des routes
 * EXACTES par page publiée, qui priment sur le catch-all /{slug} des Pages.
 * Permissions dans l'espace commun « content » (content.edit, content.publish).
 * Voir docs/modules/legal-pages.md.
 */
final class LegalPagesModule implements Module
{
    public function name(): string
    {
        return 'legal_pages';
    }

    public function boot(Router $router): void
    {
        // Back-office.
        $router->get('/admin/pages-legales', [LegalPageController::class, 'index']);
        $router->get('/admin/pages-legales/create', [LegalPageController::class, 'create']);
        $router->post('/admin/pages-legales/create', [LegalPageController::class, 'store']);
        $router->get('/admin/pages-legales/edit', [LegalPageController::class, 'edit']);
        $router->post('/admin/pages-legales/edit', [LegalPageController::class, 'update']);
        $router->post('/admin/pages-legales/delete', [LegalPageController::class, 'destroy']);

        // Front : une route EXACTE par page publiée (/<slug>). Les routes exactes
        // gagnent sur le catch-all /{slug} des Pages ; un slug inconnu retombe
        // donc naturellement sur la résolution Pages (puis 404).
        // Best-effort : la table peut ne pas exister avant la première migration.
        try {
            foreach ((new LegalPageRepository())->publishedSlugs() as $slug) {
                $router->get('/' . $slug, static function () use ($slug): void {
                    (new LegalPageController())->show(['slug' => $slug]);
                });
            }
        } catch (\Throwable) {
            // silencieux : aucune route front tant que la table n'est pas migrée
        }
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Pages légales', 'url' => '/admin/pages-legales', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_legal_pages.sql',
        ];
    }
}
