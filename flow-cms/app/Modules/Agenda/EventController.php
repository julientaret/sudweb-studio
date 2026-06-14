<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use App\Core\BlockContent;
use App\Core\Config;
use App\Core\Controller;
use App\Modules\Media\MediaFolderRepository;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;

/**
 * Agenda — back-office (CRUD + éditeur de blocs) et rendu front.
 *
 * Module indépendant : le champ « contenu » réutilise le page-builder partagé
 * (App\Core\BlockContent + blocs de base du Core). L'image vignette est une
 * option qui s'appuie sur Media : si Media est désactivé, le choix de vignette
 * disparaît mais le module reste pleinement utilisable. Permissions dans l'espace
 * commun « content » (brief §3/§7). Voir docs/modules/agenda.md.
 */
final class EventController extends Controller
{
    private EventRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new EventRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    // --- Back-office ------------------------------------------------------

    public function index(): void
    {
        $this->requirePermission('content.edit');
        $this->renderPage($this->views(), 'list', 'Agenda', [
            'events'     => $this->repo->all(),
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
        $start = $this->normalizeDate($this->input('start_date'));
        if ($title === '') {
            $this->renderEditor(null, 'Le titre est obligatoire.');
            return;
        }
        if ($start === null) {
            $this->renderEditor(null, 'La date de début est obligatoire.');
            return;
        }

        $end = $this->normalizeDate($this->input('end_date'));
        if ($end !== null && $end < $start) {
            $this->renderEditor(null, 'La date de fin ne peut pas précéder la date de début.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, null);
        $id = $this->repo->create(
            $title,
            $slug,
            $start,
            $end,
            $this->resolveThumbnail(),
            BlockContent::fromRequest(),
            $this->resolveStatus($this->input('status', 'draft')),
        );
        $this->redirect('/admin/agenda/edit?id=' . $id . '&notice=' . rawurlencode('Événement créé.'));
    }

    public function edit(): void
    {
        $this->requirePermission('content.edit');
        $event = $this->repo->find((int) $this->input('id', '0'));
        if ($event === null) {
            $this->redirect('/admin/agenda');
        }
        $this->renderEditor($event);
    }

    public function update(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $event = $this->repo->find($id);
        if ($event === null) {
            $this->redirect('/admin/agenda');
        }

        $title = $this->input('title');
        $start = $this->normalizeDate($this->input('start_date'));
        if ($title === '') {
            $this->renderEditor($event, 'Le titre est obligatoire.');
            return;
        }
        if ($start === null) {
            $this->renderEditor($event, 'La date de début est obligatoire.');
            return;
        }

        $end = $this->normalizeDate($this->input('end_date'));
        if ($end !== null && $end < $start) {
            $this->renderEditor($event, 'La date de fin ne peut pas précéder la date de début.');
            return;
        }

        $slug = $this->resolveSlug($this->input('slug') ?: $title, $id);
        $this->repo->update(
            $id,
            $title,
            $slug,
            $start,
            $end,
            $this->resolveThumbnail(),
            BlockContent::fromRequest(),
            $this->resolveStatus($this->input('status', 'draft')),
        );
        $this->redirect('/admin/agenda/edit?id=' . $id . '&notice=' . rawurlencode('Événement enregistré.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/agenda?notice=' . rawurlencode('Événement supprimé.'));
    }

    // --- Front ------------------------------------------------------------

    /**
     * @param array<string, string> $params
     */
    public function show(array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $event = $slug !== '' ? $this->repo->findPublishedBySlug($slug) : null;

        if ($event === null) {
            http_response_code(404);
            $this->renderFront('Événement introuvable', null, '<h1>404 — Événement introuvable</h1>');
            return;
        }

        $thumb = $event->thumbnailId !== null && module_enabled('Media')
            ? (new MediaRepository())->find($event->thumbnailId)
            : null;
        $this->renderFront($event->title, $event, BlockContent::render($event->content), $thumb);
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

    private function renderEditor(?Event $event, string $error = ''): void
    {
        // Vignette via Media : option dégradée proprement si le module est absent.
        $mediaEnabled = module_enabled('Media');
        $thumb = $mediaEnabled && $event !== null && $event->thumbnailId !== null
            ? (new MediaRepository())->find($event->thumbnailId)
            : null;

        $this->renderPage($this->views(), 'editor', $event ? 'Modifier l\'événement' : 'Nouvel événement', [
            'event'        => $event,
            'error'        => $error,
            'notice'       => (string) $this->input('notice', ''),
            'rows'         => BlockContent::editorRows($event?->content ?? []),
            'templates'    => BlockContent::templates(),
            'mediaEnabled' => $mediaEnabled,
            'media'        => $mediaEnabled ? (new MediaRepository())->all() : [],
            'folders'      => $mediaEnabled ? (new MediaFolderRepository())->all() : [],
            'thumb'        => $thumb,
            'canPublish'   => $this->auth->can('content.publish'),
        ]);
    }

    private function renderFront(string $title, ?Event $event, string $body, ?\App\Modules\Media\Media $thumb = null): void
    {
        $view = $this->view($this->views());
        [$thumbW, $thumbH] = $thumb !== null ? MediaUrl::intrinsic($thumb) : [0, 0];
        echo $view->render('front_layout', [
            'title'    => $title,
            'event'    => $event,
            'body'     => $body,
            // Repli original + srcset WebP responsive (déjà échappé) pour le héro.
            'thumbUrl'    => $thumb !== null ? MediaUrl::original($thumb) : null,
            'thumbSrcset' => $thumb !== null ? MediaUrl::srcset($thumb) : '',
            'thumbW'      => $thumbW,
            'thumbH'      => $thumbH,
            'siteName' => (string) Config::get('app.site_name', 'Flow'),
        ]);
    }
}
