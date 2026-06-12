<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Controller;
use App\Core\Csrf;

/**
 * Bibliothèque de documents — back-office.
 *
 * Pendant du MediaController : upload sécurisé, classement par dossiers,
 * renommage éditorial et suppression. Routeur minimal : l'`id` cible passe en
 * champ POST / paramètre de requête.
 */
final class DocumentController extends Controller
{
    private DocumentRepository $repo;
    private DocumentFolderRepository $folders;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new DocumentRepository();
        $this->folders = new DocumentFolderRepository();
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
        $url = '/admin/documents';
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
        $this->requirePermission('documents.upload');

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

        $this->renderPage($this->views(), 'library', 'Documents', [
            'items'     => $items,
            'folders'   => $this->folders->all(),
            'unfiled'   => $this->folders->countUnfiled(),
            'total'     => $this->repo->count(),
            'current'   => $current,
            'canManage' => $this->auth->can('documents.manage'),
            'notice'    => $this->input('notice'),
            'error'     => $this->input('error'),
        ]);
    }

    public function upload(): void
    {
        $this->requirePermission('documents.upload');
        $this->requireCsrf();

        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->redirect('/admin/documents?error=' . rawurlencode('Aucun fichier reçu ou upload interrompu.'));
        }
        // Sécurité : le fichier doit provenir d'un véritable upload HTTP.
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->redirect('/admin/documents?error=' . rawurlencode('Upload invalide.'));
        }

        $folder = $this->input('folder');
        try {
            $data = (new DocumentProcessor())->process(
                $file['tmp_name'],
                (string) ($file['name'] ?? 'document'),
                (int) ($file['size'] ?? 0),
            );
            $data['folder_id'] = $this->folderIdFromInput($folder);
            $this->repo->create($data);
        } catch (\Throwable $e) {
            $this->redirect($this->backUrl($folder, 'error', $e->getMessage()));
        }

        $this->redirect($this->backUrl($folder, 'notice', 'Document ajouté.'));
    }

    /**
     * Upload en AJAX, un fichier par requête (glisser-déposer multiple côté
     * client). Mêmes garde-fous que upload() mais réponse JSON, pour que le front
     * affiche une barre de progression et un statut par fichier.
     */
    public function uploadAjax(): void
    {
        $this->requirePermission('documents.upload');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'error' => 'Session expirée, rechargez la page.'], 419);
        }

        $file = $_FILES['file'] ?? null;
        $name = is_array($file) ? (string) ($file['name'] ?? 'document') : 'document';
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
            $data = (new DocumentProcessor())->process(
                $file['tmp_name'],
                (string) ($file['name'] ?? 'document'),
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
        if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
            // Limite imposée par PHP (upload_max_filesize / post_max_size), souvent
            // plus basse que notre config : on annonce la vraie limite serveur.
            $mo = max(1, (int) floor(DocumentProcessor::effectiveMaxSize() / (1024 * 1024)));
            return 'Fichier trop volumineux (limite serveur : ' . $mo . ' Mo).';
        }
        return match ($code) {
            UPLOAD_ERR_PARTIAL => 'Upload interrompu.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier reçu.',
            default            => 'Échec de l’upload.',
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

    /** Déplace un document vers un dossier (ou « Non classé »). */
    public function move(): void
    {
        $this->requirePermission('documents.upload');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        if ($this->repo->find($id) !== null) {
            $this->repo->move($id, $this->folderIdFromInput($this->input('folder_id')));
        }
        $this->redirect($this->backUrl($this->input('folder'), 'notice', 'Document déplacé.'));
    }

    public function createFolder(): void
    {
        $this->requirePermission('documents.manage');
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
        $this->requirePermission('documents.manage');
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
        $this->requirePermission('documents.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $this->folders->delete($id); // les documents repassent en « Non classé »
        $this->redirect($this->backUrl('all', 'notice', 'Dossier supprimé (documents conservés).'));
    }

    public function updateTitle(): void
    {
        $this->requirePermission('documents.upload');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        if ($this->repo->find($id) !== null) {
            $this->repo->updateTitle($id, $this->input('title'));
        }
        $this->redirect($this->backUrl($this->input('folder'), 'notice', 'Libellé enregistré.'));
    }

    public function delete(): void
    {
        $this->requirePermission('documents.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $document = $this->repo->find($id);
        if ($document !== null) {
            (new DocumentProcessor())->deleteFiles($document);
            $this->repo->delete($id);
        }
        $this->redirect($this->backUrl($this->input('folder'), 'notice', 'Document supprimé.'));
    }
}
