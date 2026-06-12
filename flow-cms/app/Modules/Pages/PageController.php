<?php

declare(strict_types=1);

namespace App\Modules\Pages;

use App\Core\BlockContent;
use App\Core\Config;
use App\Core\Controller;
use App\Modules\Media\MediaFolderRepository;
use App\Modules\Media\MediaRepository;

/**
 * Pages — back-office (CRUD + éditeur de blocs) et rendu front (brief §4).
 */
final class PageController extends Controller
{
    private PageRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new PageRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    // --- Back-office ------------------------------------------------------

    public function index(): void
    {
        $this->requirePermission('content.edit');
        $this->renderPage($this->views(), 'list', 'Pages', [
            'pages'  => $this->repo->all(),
            'notice' => $this->input('notice'),
            'canPublish' => $this->auth->can('content.publish'),
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
        $content = BlockContent::fromRequest();
        $status = $this->resolveStatus($this->input('status', 'draft'));

        $id = $this->repo->create($title, $slug, $content, $status);
        $this->redirect('/admin/pages/edit?id=' . $id . '&notice=' . rawurlencode('Page créée.'));
    }

    public function edit(): void
    {
        $this->requirePermission('content.edit');
        $page = $this->repo->find((int) $this->input('id', '0'));
        if ($page === null) {
            $this->redirect('/admin/pages');
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
            $this->redirect('/admin/pages');
        }

        $title = $this->input('title');
        if ($title === '') {
            $this->renderEditor($page, 'Le titre est obligatoire.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, $id);
        $content = BlockContent::fromRequest();
        $status = $this->resolveStatus($this->input('status', 'draft'));

        $this->repo->update($id, $title, $slug, $content, $status);
        $this->redirect('/admin/pages/edit?id=' . $id . '&notice=' . rawurlencode('Page enregistrée.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/pages?notice=' . rawurlencode('Page supprimée.'));
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

        $this->renderFront($page->title, BlockContent::render($page->content));
    }

    // --- Interne ----------------------------------------------------------

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

    private function renderEditor(?Page $page, string $error = ''): void
    {
        $this->renderPage($this->views(), 'editor', $page ? 'Modifier la page' : 'Nouvelle page', [
            'page'       => $page,
            'error'      => $error,
            'notice'     => (string) $this->input('notice', ''),
            'rows'       => BlockContent::editorRows($page?->content ?? [], PagesModule::BLOCK_LABELS),
            'templates'  => BlockContent::templates(PagesModule::BLOCK_LABELS),
            'media'      => (new MediaRepository())->all(),
            'folders'    => (new MediaFolderRepository())->all(),
            'canPublish' => $this->auth->can('content.publish'),
        ]);
    }

    private function renderFront(string $title, string $body): void
    {
        // Le thème du site public (frontend) peut surcharger 'front_layout' :
        // ses dossiers, fournis par app.front_views, sont cherchés en premier.
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
