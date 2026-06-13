<?php

declare(strict_types=1);

namespace App\Modules\LegalPages;

use App\Core\Config;
use App\Core\Controller;
use App\Modules\Pages\DeltaRenderer;

/**
 * Pages légales — back-office (CRUD avec éditeur de texte riche) et rendu front.
 *
 * Le contenu est un Delta Quill (JSON), borné à la sauvegarde (mêmes règles que
 * App\Modules\Pages\Blocks\RichtextBlock) et rendu en HTML par DeltaRenderer.
 * Permissions dans l'espace commun « content » (content.edit / content.publish,
 * brief §3/§7). Voir docs/modules/legal-pages.md.
 */
final class LegalPageController extends Controller
{
    private LegalPageRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new LegalPageRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    // --- Back-office ------------------------------------------------------

    public function index(): void
    {
        $this->requirePermission('content.edit');
        $this->renderPage($this->views(), 'list', 'Pages légales', [
            'pages'  => $this->repo->all(),
            'notice' => (string) $this->input('notice'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('content.edit');
        $this->renderEditor(null);
    }

    public function store(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();

        $title = $this->input('title');
        if ($title === '') {
            $this->renderEditor(null, 'Le titre est obligatoire.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, null);
        $id = $this->repo->create(
            $title,
            $slug,
            $this->sanitizeContent($this->input('content')),
            $this->resolveStatus($this->input('status', 'published')),
        );
        $this->redirect('/admin/pages-legales/edit?id=' . $id . '&notice=' . rawurlencode('Page créée.'));
    }

    public function edit(): void
    {
        $this->requirePermission('content.edit');
        $page = $this->repo->find((int) $this->input('id', '0'));
        if ($page === null) {
            $this->redirect('/admin/pages-legales');
        }
        $this->renderEditor($page);
    }

    public function update(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $page = $this->repo->find($id);
        if ($page === null) {
            $this->redirect('/admin/pages-legales');
        }

        $title = $this->input('title');
        if ($title === '') {
            $this->renderEditor($page, 'Le titre est obligatoire.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, $id);
        $this->repo->update(
            $id,
            $title,
            $slug,
            $this->sanitizeContent($this->input('content')),
            $this->resolveStatus($this->input('status', 'published')),
        );
        $this->redirect('/admin/pages-legales/edit?id=' . $id . '&notice=' . rawurlencode('Page enregistrée.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/pages-legales?notice=' . rawurlencode('Page supprimée.'));
    }

    // --- Front ------------------------------------------------------------

    /**
     * @param array<string, string> $params
     */
    public function show(array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $page = $slug !== '' ? $this->repo->findPublishedBySlug($slug) : null;

        if ($page === null) {
            http_response_code(404);
            $this->renderFront('Page introuvable', '<h1>404 — Page introuvable</h1>');
            return;
        }

        $this->renderFront($page->title, DeltaRenderer::toHtml($page->content));
    }

    // --- Interne ----------------------------------------------------------

    /**
     * Borne le Delta reçu : on ne conserve que des ops dont l'insert est une
     * chaîne (prose), avec leurs attributs (mêmes règles que RichtextBlock).
     *
     * @return array{ops: array<int, array<string, mixed>>}
     */
    private function sanitizeContent(string $raw): array
    {
        $delta = $raw !== '' ? json_decode($raw, true) : null;
        $ops = [];
        if (is_array($delta) && isset($delta['ops']) && is_array($delta['ops'])) {
            foreach ($delta['ops'] as $op) {
                if (is_array($op) && isset($op['insert']) && is_string($op['insert'])) {
                    $clean = ['insert' => $op['insert']];
                    if (isset($op['attributes']) && is_array($op['attributes'])) {
                        $clean['attributes'] = $op['attributes'];
                    }
                    $ops[] = $clean;
                }
            }
        }
        return ['ops' => $ops];
    }

    private function resolveStatus(string $requested): string
    {
        $status = $requested === 'published' ? 'published' : 'draft';
        // Un éditeur sans content.publish ne peut pas publier : on retombe en brouillon.
        if ($status === 'published' && !$this->auth->can('content.publish')) {
            $status = 'draft';
        }
        return $status;
    }

    private function resolveSlug(string $base, ?int $exceptId): string
    {
        $slug = slug($base);
        $candidate = $slug;
        $i = 2;
        while ($this->repo->slugExists($candidate, $exceptId)) {
            $candidate = $slug . '-' . $i;
            $i++;
        }
        return $candidate;
    }

    private function renderEditor(?LegalPage $page, string $error = ''): void
    {
        $this->renderPage($this->views(), 'editor', $page ? 'Modifier la page légale' : 'Nouvelle page légale', [
            'page'       => $page,
            'error'      => $error,
            'notice'     => (string) $this->input('notice', ''),
            'canPublish' => $this->auth->can('content.publish'),
        ]);
    }

    private function renderFront(string $title, string $body): void
    {
        // Le thème du site public (frontend) peut surcharger 'front_layout' :
        // ses dossiers, fournis par app.front_views, sont cherchés en premier
        // (même résolution que App\Modules\Pages\PageController::renderFront).
        $theme = Config::get('app.front_views', []);
        $paths = array_merge(is_array($theme) ? $theme : [], $this->views());

        $view = $this->view($paths);
        echo $view->render('front_layout', [
            'title'    => $title,
            'body'     => $body,
            'siteName' => (string) Config::get('app.site_name', 'Flow'),
        ]);
    }
}
