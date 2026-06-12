<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Controller;
use App\Core\Csrf;

/**
 * Bibliothèque d'images — back-office (brief §5).
 *
 * Routeur minimal : l'`id` cible passe en champ POST / paramètre de requête.
 */
final class MediaController extends Controller
{
    private MediaRepository $repo;
    private MediaFolderRepository $folders;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new MediaRepository();
        $this->folders = new MediaFolderRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    /**
     * Convertit un paramètre de dossier ('', 'all', 'none' ou un id) en id réel
     * de dossier existant, ou null pour « Non classé ».
     */
    private function folderIdFromInput(string $value): ?int
    {
        if ($value !== '' && ctype_digit($value) && $this->folders->find((int) $value) !== null) {
            return (int) $value;
        }
        return null;
    }

    /** URL de retour vers la bibliothèque en conservant le dossier courant. */
    private function backUrl(string $folder, string $type, string $message): string
    {
        $url = '/admin/media';
        if ($folder !== '' && $folder !== 'all') {
            $url .= '?folder=' . rawurlencode($folder);
            $url .= '&' . $type . '=' . rawurlencode($message);
        } else {
            $url .= '?' . $type . '=' . rawurlencode($message);
        }
        return $url;
    }

    public function index(): void
    {
        $this->requirePermission('media.upload');

        $sel = $this->input('folder'); // '', 'all', 'none' ou id
        if ($sel === 'none') {
            $items = $this->repo->allInFolder(null);
            $current = 'none';
        } elseif ($sel !== '' && ctype_digit($sel) && $this->folders->find((int) $sel) !== null) {
            $items = $this->repo->allInFolder((int) $sel);
            $current = $sel;
        } else {
            $items = $this->repo->all();
            $current = 'all';
        }

        $this->renderPage($this->views(), 'library', 'Médias', [
            'items'     => $items,
            'folders'   => $this->folders->all(),
            'unfiled'   => $this->folders->countUnfiled(),
            'total'     => $this->repo->count(),
            'current'   => $current,
            'canManage' => $this->auth->can('media.manage'),
            'notice'    => $this->input('notice'),
            'error'     => $this->input('error'),
        ]);
    }

    public function upload(): void
    {
        $this->requirePermission('media.upload');
        $this->requireCsrf();

        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->redirect('/admin/media?error=' . rawurlencode('Aucun fichier reçu ou upload interrompu.'));
        }
        // Sécurité : le fichier doit provenir d'un véritable upload HTTP.
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->redirect('/admin/media?error=' . rawurlencode('Upload invalide.'));
        }

        $folder = $this->input('folder');
        try {
            $data = (new MediaProcessor())->process(
                $file['tmp_name'],
                (string) ($file['name'] ?? 'image'),
                (int) ($file['size'] ?? 0),
            );
            $data['folder_id'] = $this->folderIdFromInput($folder);
            $this->repo->create($data);
        } catch (\Throwable $e) {
            $this->redirect($this->backUrl($folder, 'error', $e->getMessage()));
        }

        $this->redirect($this->backUrl($folder, 'notice', 'Image ajoutée.'));
    }

    /**
     * Upload en AJAX, un fichier par requête (glisser-déposer multiple côté
     * client). Mêmes garde-fous que upload() mais réponse JSON, pour que le front
     * affiche une barre de progression et un statut par image.
     */
    public function uploadAjax(): void
    {
        $this->requirePermission('media.upload');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'error' => 'Session expirée, rechargez la page.'], 419);
        }

        $file = $_FILES['file'] ?? null;
        $name = is_array($file) ? (string) ($file['name'] ?? 'image') : 'image';
        $code = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

        if (!is_array($file) || $code !== UPLOAD_ERR_OK) {
            $this->json(['ok' => false, 'name' => $name, 'error' => $this->uploadErrorMessage($code)], 400);
        }
        // Sécurité : le fichier doit provenir d'un véritable upload HTTP.
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->json(['ok' => false, 'name' => $name, 'error' => 'Upload invalide.'], 400);
        }

        $folder = $this->input('folder');
        try {
            $data = (new MediaProcessor())->process(
                $file['tmp_name'],
                (string) ($file['name'] ?? 'image'),
                (int) ($file['size'] ?? 0),
            );
            $data['folder_id'] = $this->folderIdFromInput($folder);
            $id = $this->repo->create($data);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'name' => $name, 'error' => $e->getMessage()], 422);
        }

        $this->json(['ok' => true, 'id' => $id, 'name' => $name]);
    }

    /** Message lisible pour un code d'erreur d'upload PHP. */
    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux.',
            UPLOAD_ERR_PARTIAL                        => 'Upload interrompu.',
            UPLOAD_ERR_NO_FILE                        => 'Aucun fichier reçu.',
            default                                   => 'Échec de l’upload.',
        };
    }

    /**
     * Réponse JSON terminale (pour les appels AJAX).
     *
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
        exit;
    }

    /** Déplace une image vers un dossier (ou « Non classé »). */
    public function move(): void
    {
        $this->requirePermission('media.upload');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        if ($this->repo->find($id) !== null) {
            $this->repo->move($id, $this->folderIdFromInput($this->input('folder_id')));
        }
        $this->redirect($this->backUrl($this->input('folder'), 'notice', 'Image déplacée.'));
    }

    public function createFolder(): void
    {
        $this->requirePermission('media.manage');
        $this->requireCsrf();

        $name = $this->input('name');
        if ($name === '') {
            $this->redirect($this->backUrl($this->input('folder'), 'error', 'Nom de dossier requis.'));
        }
        if ($this->folders->nameExists($name)) {
            $this->redirect($this->backUrl($this->input('folder'), 'error', 'Ce dossier existe déjà.'));
        }
        $id = $this->folders->create($name);
        $this->redirect($this->backUrl((string) $id, 'notice', 'Dossier créé.'));
    }

    public function renameFolder(): void
    {
        $this->requirePermission('media.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $name = $this->input('name');
        if ($this->folders->find($id) !== null && $name !== '' && !$this->folders->nameExists($name, $id)) {
            $this->folders->rename($id, $name);
        }
        $this->redirect($this->backUrl((string) $id, 'notice', 'Dossier renommé.'));
    }

    public function deleteFolder(): void
    {
        $this->requirePermission('media.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $this->folders->delete($id); // les images repassent en « Non classé »
        $this->redirect($this->backUrl('all', 'notice', 'Dossier supprimé (images conservées).'));
    }

    public function updateAlt(): void
    {
        $this->requirePermission('media.upload');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        if ($this->repo->find($id) !== null) {
            $this->repo->updateAlt($id, $this->input('alt'));
        }
        $this->redirect($this->backUrl($this->input('folder'), 'notice', 'Texte alternatif enregistré.'));
    }

    public function editor(): void
    {
        $this->requirePermission('media.upload');
        $media = $this->repo->find((int) $this->input('id', '0'));
        if ($media === null) {
            $this->redirect('/admin/media');
        }
        $this->renderPage($this->views(), 'crop', 'Recadrer — ' . $media->originalName, [
            'media' => $media,
            'error' => $this->input('error'),
        ]);
    }

    public function crop(): void
    {
        $this->requirePermission('media.upload');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $media = $this->repo->find($id);
        if ($media === null) {
            $this->redirect('/admin/media');
        }

        $x = max(0, (int) $this->input('x', '0'));
        $y = max(0, (int) $this->input('y', '0'));
        $w = (int) $this->input('w', '0');
        $h = (int) $this->input('h', '0');
        if ($w < 1 || $h < 1) {
            $this->redirect('/admin/media/edit?id=' . $id . '&error=' . rawurlencode('Sélection de recadrage invalide.'));
        }

        $this->repo->updateCrop($id, $x, $y, $w, $h);
        // Recharge pour disposer des coords, puis régénère WebP + presets (original intact).
        (new MediaProcessor())->regenerate($this->repo->find($id));
        $this->redirect('/admin/media?notice=' . rawurlencode('Recadrage appliqué.'));
    }

    public function resetCrop(): void
    {
        $this->requirePermission('media.upload');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $media = $this->repo->find($id);
        if ($media !== null) {
            $this->repo->clearCrop($id);
            (new MediaProcessor())->regenerate($this->repo->find($id));
        }
        $this->redirect('/admin/media?notice=' . rawurlencode('Recadrage réinitialisé.'));
    }

    public function delete(): void
    {
        $this->requirePermission('media.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $media = $this->repo->find($id);
        if ($media !== null) {
            (new MediaProcessor())->deleteFiles($media);
            $this->repo->delete($id);
        }
        $this->redirect($this->backUrl($this->input('folder'), 'notice', 'Image supprimée.'));
    }
}
