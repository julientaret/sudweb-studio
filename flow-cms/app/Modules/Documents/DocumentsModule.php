<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Module;
use App\Core\Router;

/**
 * Module Documents — bibliothèque de documents réutilisables.
 *
 * Pendant du module Media, mais pour des fichiers bureautiques (PDF, Word, Excel,
 * PowerPoint, OpenDocument, texte…). Brique transverse : les autres modules / les
 * pages référencent un document par son lien public, copié depuis la bibliothèque.
 * Voir docs/modules/documents.md.
 */
final class DocumentsModule implements Module
{
    public function name(): string
    {
        return 'documents';
    }

    public function boot(Router $router): void
    {
        $router->get('/admin/documents', [DocumentController::class, 'index']);
        $router->post('/admin/documents/upload', [DocumentController::class, 'upload']);
        $router->post('/admin/documents/upload/ajax', [DocumentController::class, 'uploadAjax']);
        $router->post('/admin/documents/title', [DocumentController::class, 'updateTitle']);
        $router->post('/admin/documents/move', [DocumentController::class, 'move']);
        $router->post('/admin/documents/folder/create', [DocumentController::class, 'createFolder']);
        $router->post('/admin/documents/folder/rename', [DocumentController::class, 'renameFolder']);
        $router->post('/admin/documents/folder/delete', [DocumentController::class, 'deleteFolder']);
        $router->post('/admin/documents/delete', [DocumentController::class, 'delete']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Documents', 'url' => '/admin/documents', 'permission' => 'documents.upload'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_documents.sql',
            __DIR__ . '/migrations/002_create_document_folders.sql',
        ];
    }
}
