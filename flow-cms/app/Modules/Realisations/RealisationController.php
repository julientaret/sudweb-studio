<?php

declare(strict_types=1);

namespace App\Modules\Realisations;

use App\Core\Blocks\RichtextBlock;
use App\Core\Controller;
use App\Modules\Media\MediaFolderRepository;
use App\Modules\Media\MediaRepository;

/**
 * Réalisations — back-office (CRUD avec titre, description rich text et photo).
 *
 * Module indépendant : la description réutilise l'éditeur de texte riche du Core
 * (Quill + App\Core\DeltaRenderer), la photo s'appuie sur Media (référencée par
 * ID). Si Media est désactivé, le choix de photo disparaît mais le module reste
 * pleinement utilisable. Permissions dans l'espace commun « content » (brief §3/§7).
 * Aucune route front : l'affichage passe par le bloc dynamique `realisations`.
 */
final class RealisationController extends Controller
{
    private RealisationRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new RealisationRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    public function index(): void
    {
        $this->requirePermission('content.edit');
        $this->renderPage($this->views(), 'list', 'Réalisations', [
            'items'  => $this->repo->all(),
            'notice' => $this->input('notice'),
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

        $id = $this->repo->create(
            $title,
            $this->sanitizeDescription(),
            $this->resolveMedia(),
            (int) $this->input('position', '0'),
            $this->resolveStatus($this->input('status', 'draft')),
        );
        $this->redirect('/admin/realisations/edit?id=' . $id . '&notice=' . rawurlencode('Réalisation créée.'));
    }

    public function edit(): void
    {
        $this->requirePermission('content.edit');
        $item = $this->repo->find((int) $this->input('id', '0'));
        if ($item === null) {
            $this->redirect('/admin/realisations');
        }
        $this->renderEditor($item);
    }

    public function update(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $item = $this->repo->find($id);
        if ($item === null) {
            $this->redirect('/admin/realisations');
        }

        $title = $this->input('title');
        if ($title === '') {
            $this->renderEditor($item, 'Le titre est obligatoire.');
            return;
        }

        $this->repo->update(
            $id,
            $title,
            $this->sanitizeDescription(),
            $this->resolveMedia(),
            (int) $this->input('position', '0'),
            $this->resolveStatus($this->input('status', 'draft')),
        );
        $this->redirect('/admin/realisations/edit?id=' . $id . '&notice=' . rawurlencode('Réalisation enregistrée.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('content.edit');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/realisations?notice=' . rawurlencode('Réalisation supprimée.'));
    }

    // --- Interne ----------------------------------------------------------

    /**
     * Delta Quill borné depuis le POST (réutilise le sanitizer du bloc Texte du
     * Core : prose autorisée seulement, aucun HTML arbitraire).
     *
     * @return array<string, mixed> {ops:[...]}
     */
    private function sanitizeDescription(): array
    {
        $raw = $_POST['description'] ?? null;
        return (new RichtextBlock())->sanitize(['delta' => $raw])['delta'];
    }

    /**
     * ID média de la photo depuis le POST, ou null si non choisie/inconnue.
     */
    private function resolveMedia(): ?int
    {
        if (!module_enabled('Media')) {
            return null;
        }
        $id = (int) $this->input('media_id', '0');
        if ($id <= 0) {
            return null;
        }
        return (new MediaRepository())->find($id) !== null ? $id : null;
    }

    private function resolveStatus(string $requested): string
    {
        $status = $requested === 'published' ? 'published' : 'draft';
        // Un éditeur sans content.publish ne peut pas publier : retour en brouillon.
        if ($status === 'published' && !$this->auth->can('content.publish')) {
            $status = 'draft';
        }
        return $status;
    }

    private function renderEditor(?Realisation $item, string $error = ''): void
    {
        $mediaEnabled = module_enabled('Media');
        $photo = $mediaEnabled && $item !== null && $item->mediaId !== null
            ? (new MediaRepository())->find($item->mediaId)
            : null;

        $this->renderPage($this->views(), 'editor', $item ? 'Modifier la réalisation' : 'Nouvelle réalisation', [
            'item'         => $item,
            'error'        => $error,
            'notice'       => (string) $this->input('notice', ''),
            'mediaEnabled' => $mediaEnabled,
            'media'        => $mediaEnabled ? (new MediaRepository())->all() : [],
            'folders'      => $mediaEnabled ? (new MediaFolderRepository())->all() : [],
            'photo'        => $photo,
            'canPublish'   => $this->auth->can('content.publish'),
        ]);
    }
}
