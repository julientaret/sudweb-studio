<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Accueil de l'admin : tableau de bord avec accès rapide aux sections.
 *
 * Les cartes sont dérivées du menu admin agrégé des modules, filtré par
 * permission (cf. Controller::visibleMenu) : le dashboard s'adapte donc tout
 * seul aux modules activés et aux droits de l'utilisateur connecté.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        // Accès rapide : menu filtré par permission, réordonné et filtré selon les
        // préférences du compte. Le tutoriel reste fixe (non déplaçable, non masquable)
        // et est rendu à part par la vue.
        [$visible, $hidden] = $this->arrangedCards($this->visibleMenu());

        $this->renderPage([__DIR__ . '/views'], 'dashboard', 'Tableau de bord', [
            'cards'   => $visible,
            'hidden'  => $hidden,
            'recent'  => $this->recentContent(),
            'gallery' => $this->galleryImages(),
            'user'    => $this->auth->user(),
            'csrf'    => \App\Core\Csrf::token(),
        ]);
    }

    /**
     * Enregistre l'agencement de l'accès rapide pour l'utilisateur courant.
     *
     * Reçoit `order` (urls dans l'ordre voulu) et `hidden` (urls masquées) en JSON.
     * Les valeurs sont restreintes à l'univers des cartes autorisées (anti-injection).
     */
    public function saveLayout(): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        $allowed = [];
        foreach ($this->visibleMenu() as $c) {
            $allowed[$c['url']] = true;
        }

        $keep = static fn (array $urls): array => array_values(array_filter(
            $urls,
            static fn (string $u): bool => isset($allowed[$u])
        ));

        $order = $keep($this->jsonUrls($_POST['order'] ?? null));
        $hidden = $keep($this->jsonUrls($_POST['hidden'] ?? null));

        $json = (string) json_encode(['order' => $order, 'hidden' => $hidden], JSON_UNESCAPED_SLASHES);

        $user = $this->auth->user();
        if ($user !== null) {
            (new \App\Modules\Users\UserRepository())->updateDashboardPrefs($user->id, $json);
        }

        echo json_encode(['ok' => true]);
    }

    /**
     * Décode un paramètre JSON en liste de chaînes (urls).
     *
     * @return array<int, string>
     */
    private function jsonUrls(mixed $raw): array
    {
        if (!is_string($raw)) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        return array_values(array_filter($data, 'is_string'));
    }

    /**
     * Applique les préférences du compte à la liste des cartes du menu.
     *
     * @param array<int, array{label:string, url:string}> $menu
     * @return array{0: array<int, array{label:string, url:string}>, 1: array<int, array{label:string, url:string}>}
     *         [cartes visibles ordonnées, cartes masquées]
     */
    private function arrangedCards(array $menu): array
    {
        $prefs = $this->dashboardPrefs();

        $byUrl = [];
        foreach ($menu as $c) {
            $byUrl[$c['url']] = $c;
        }

        // Cartes connues dans l'ordre enregistré, puis les nouvelles (modules
        // récemment activés) appendues à la fin.
        $ordered = [];
        foreach ($prefs['order'] as $url) {
            if (isset($byUrl[$url])) {
                $ordered[$url] = $byUrl[$url];
            }
        }
        foreach ($byUrl as $url => $c) {
            $ordered[$url] ??= $c;
        }

        $visible = [];
        $hidden = [];
        foreach ($ordered as $url => $c) {
            if (in_array($url, $prefs['hidden'], true)) {
                $hidden[] = $c;
            } else {
                $visible[] = $c;
            }
        }

        return [$visible, $hidden];
    }

    /**
     * Préférences du tableau de bord du compte courant (toujours bien formées).
     *
     * @return array{order: array<int, string>, hidden: array<int, string>}
     */
    private function dashboardPrefs(): array
    {
        $default = ['order' => [], 'hidden' => []];

        $user = $this->auth->user();
        if ($user === null || $user->dashboardPrefs === null || $user->dashboardPrefs === '') {
            return $default;
        }

        $data = json_decode($user->dashboardPrefs, true);
        if (!is_array($data)) {
            return $default;
        }

        return [
            'order'  => array_values(array_filter((array) ($data['order'] ?? []), 'is_string')),
            'hidden' => array_values(array_filter((array) ($data['hidden'] ?? []), 'is_string')),
        ];
    }

    /**
     * Derniers contenus modifiés, tous modules de contenu confondus (Pages,
     * Agenda), normalisés et triés par date de modification décroissante. Chaque
     * item porte son URL d'édition propre pour que le dashboard reste agnostique.
     *
     * @return array<int, array{title:string,status:string,published:bool,updatedAt:string,editUrl:string,kind:string,badge:string}>
     */
    private function recentContent(): array
    {
        $enabled = (array) Config::get('modules.enabled', []);
        $items = [];

        if (in_array('Pages', $enabled, true)) {
            foreach ((new \App\Modules\Pages\PageRepository())->all() as $p) {
                $items[] = [
                    'title'     => $p->title,
                    'status'    => $p->status,
                    'published' => $p->isPublished(),
                    'updatedAt' => $p->updatedAt,
                    'editUrl'   => '/admin/pages/edit?id=' . $p->id,
                    'kind'      => 'Page',
                    'badge'     => $p->isPublished() ? 'Publiée' : 'Brouillon',
                ];
            }
        }

        if (in_array('Agenda', $enabled, true)) {
            foreach ((new \App\Modules\Agenda\EventRepository())->all() as $ev) {
                $items[] = [
                    'title'     => $ev->title,
                    'status'    => $ev->status,
                    'published' => $ev->isPublished(),
                    'updatedAt' => $ev->updatedAt,
                    'editUrl'   => '/admin/agenda/edit?id=' . $ev->id,
                    'kind'      => 'Événement',
                    'badge'     => $ev->isPublished() ? 'Publié' : 'Brouillon',
                ];
            }
        }

        if (in_array('Actualites', $enabled, true)) {
            foreach ((new \App\Modules\Actualites\ArticleRepository())->all() as $a) {
                $items[] = [
                    'title'     => $a->title,
                    'status'    => $a->status,
                    'published' => $a->isPublished(),
                    'updatedAt' => $a->updatedAt,
                    'editUrl'   => '/admin/actualites/edit?id=' . $a->id,
                    'kind'      => 'Actualité',
                    'badge'     => $a->isPublished() ? 'Publiée' : 'Brouillon',
                ];
            }
        }

        // Tri par updatedAt (format 'Y-m-d H:i:s' → comparaison lexicale = chronologique).
        usort($items, static fn (array $a, array $b): int => strcmp($b['updatedAt'], $a['updatedAt']));

        return array_slice($items, 0, 6);
    }

    /**
     * Images de la bibliothèque pour la visionneuse — uniquement si le module
     * Media est activé (sinon sa table peut ne pas exister). Vide => pas de
     * visionneuse affichée.
     *
     * @return array<int, \App\Modules\Media\Media>
     */
    private function galleryImages(): array
    {
        $enabled = (array) Config::get('modules.enabled', []);
        if (!in_array('Media', $enabled, true)) {
            return [];
        }
        return array_slice((new \App\Modules\Media\MediaRepository())->all(), 0, 12);
    }
}
