<?php

declare(strict_types=1);

namespace App\Modules\Actualites;

use App\Core\BlockContent;
use App\Core\Config;
use App\Core\Controller;
use App\Modules\Media\MediaFolderRepository;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;

/**
 * Actualités — back-office (CRUD + éditeur de blocs) et rendu front.
 *
 * Module indépendant : le champ « contenu » réutilise le page-builder partagé
 * (App\Core\BlockContent + blocs de base du Core). L'image vignette est une
 * option qui s'appuie sur Media : si Media est désactivé, le choix de vignette
 * disparaît mais le module reste pleinement utilisable. Permissions dans l'espace
 * commun « content » (brief §3/§7). Voir docs/modules/actualites.md.
 */
final class ArticleController extends Controller
{
    private ArticleRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new ArticleRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    // --- Back-office ------------------------------------------------------

    public function index(): void
    {
        $this->requirePermission('content.edit');
        $this->renderPage($this->views(), 'list', 'Actualités', [
            'articles'   => $this->repo->all(),
            'notice'     => $this->input('notice'),
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
        $date = $this->normalizeDate($this->input('published_date'));
        if ($title === '') {
            $this->renderEditor(null, 'Le titre est obligatoire.');
            return;
        }
        if ($date === null) {
            $this->renderEditor(null, 'La date de publication est obligatoire.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, null);
        $id = $this->repo->create(
            $title,
            $slug,
            $date,
            $this->resolveThumbnail(),
            BlockContent::fromRequest(),
            $this->resolveStatus($this->input('status', 'draft')),
        );
        $this->redirect('/admin/actualites/edit?id=' . $id . '&notice=' . rawurlencode('Actualité créée.'));
    }

    public function edit(): void
    {
        $this->requirePermission('content.edit');
        $article = $this->repo->find((int) $this->input('id', '0'));
        if ($article === null) {
            $this->redirect('/admin/actualites');
        }
        $this->renderEditor($article);
    }

    public function update(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $article = $this->repo->find($id);
        if ($article === null) {
            $this->redirect('/admin/actualites');
        }

        $title = $this->input('title');
        $date = $this->normalizeDate($this->input('published_date'));
        if ($title === '') {
            $this->renderEditor($article, 'Le titre est obligatoire.');
            return;
        }
        if ($date === null) {
            $this->renderEditor($article, 'La date de publication est obligatoire.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, $id);
        $this->repo->update(
            $id,
            $title,
            $slug,
            $date,
            $this->resolveThumbnail(),
            BlockContent::fromRequest(),
            $this->resolveStatus($this->input('status', 'draft')),
        );
        $this->redirect('/admin/actualites/edit?id=' . $id . '&notice=' . rawurlencode('Actualité enregistrée.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/actualites?notice=' . rawurlencode('Actualité supprimée.'));
    }

    // --- Front ------------------------------------------------------------

    /**
     * @param array<string, string> $params
     */
    public function show(array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $article = $slug !== '' ? $this->repo->findPublishedBySlug($slug) : null;

        if ($article === null) {
            http_response_code(404);
            $this->renderFront('Actualité introuvable', null, '<h1>404 — Actualité introuvable</h1>');
            return;
        }

        $thumb = $article->thumbnailId !== null && module_enabled('Media')
            ? (new MediaRepository())->find($article->thumbnailId)
            : null;
        $this->renderFront($article->title, $article, BlockContent::render($article->content), $thumb);
    }

    // --- Interne ----------------------------------------------------------

    /**
     * Valide une date `Y-m-d` ; retourne la date normalisée ou null si vide/invalide.
     */
    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * ID média de la vignette depuis le POST, ou null si non choisie/inconnue.
     */
    private function resolveThumbnail(): ?int
    {
        if (!module_enabled('Media')) {
            return null;
        }
        $id = (int) $this->input('thumbnail_id', '0');
        if ($id <= 0) {
            return null;
        }
        return (new MediaRepository())->find($id) !== null ? $id : null;
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

    private function renderEditor(?Article $article, string $error = ''): void
    {
        // Vignette via Media : option dégradée proprement si le module est absent.
        $mediaEnabled = module_enabled('Media');
        $thumb = $mediaEnabled && $article !== null && $article->thumbnailId !== null
            ? (new MediaRepository())->find($article->thumbnailId)
            : null;

        $this->renderPage($this->views(), 'editor', $article ? 'Modifier l\'actualité' : 'Nouvelle actualité', [
            'article'      => $article,
            'error'        => $error,
            'notice'       => (string) $this->input('notice', ''),
            'rows'         => BlockContent::editorRows($article?->content ?? []),
            'templates'    => BlockContent::templates(),
            'mediaEnabled' => $mediaEnabled,
            'media'        => $mediaEnabled ? (new MediaRepository())->all() : [],
            'folders'      => $mediaEnabled ? (new MediaFolderRepository())->all() : [],
            'thumb'        => $thumb,
            'canPublish'   => $this->auth->can('content.publish'),
        ]);
    }

    private function renderFront(string $title, ?Article $article, string $body, ?\App\Modules\Media\Media $thumb = null): void
    {
        $view = $this->view($this->views());
        echo $view->render('front_layout', [
            'title'    => $title,
            'article'  => $article,
            'body'     => $body,
            'thumbUrl' => $thumb !== null ? MediaUrl::size($thumb, 'large') : null,
            'siteName' => (string) Config::get('app.site_name', 'Flow'),
        ]);
    }
}
