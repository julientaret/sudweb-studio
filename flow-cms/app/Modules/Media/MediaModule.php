<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Kernel;
use App\Core\Module;
use App\Core\Router;
use App\Modules\Media\Blocks\GalleryBlock;
use App\Modules\Media\Blocks\ImageBlock;

/**
 * Module Media — bibliothèque d'images (brief §5).
 *
 * Brique transverse : les autres modules référenceront un média par son `id`
 * (jamais par URL). Fournit aussi le bloc « image » du page-builder, qui n'est
 * donc proposé dans les éditeurs que lorsque Media est actif. Voir
 * docs/modules/media.md.
 */
final class MediaModule implements Module
{
    public function name(): string
    {
        return 'media';
    }

    public function boot(Router $router): void
    {
        // Blocs image/galerie du page-builder (brief §4 : enregistrés dans boot()).
        Kernel::instance()->blocks()->register(new ImageBlock());
        Kernel::instance()->blocks()->register(new GalleryBlock());

        $router->get('/admin/media', [MediaController::class, 'index']);
        $router->post('/admin/media/upload', [MediaController::class, 'upload']);
        $router->post('/admin/media/upload/ajax', [MediaController::class, 'uploadAjax']);
        $router->post('/admin/media/alt', [MediaController::class, 'updateAlt']);
        $router->post('/admin/media/move', [MediaController::class, 'move']);
        $router->post('/admin/media/folder/create', [MediaController::class, 'createFolder']);
        $router->post('/admin/media/folder/rename', [MediaController::class, 'renameFolder']);
        $router->post('/admin/media/folder/delete', [MediaController::class, 'deleteFolder']);
        $router->get('/admin/media/edit', [MediaController::class, 'editor']);
        $router->post('/admin/media/crop', [MediaController::class, 'crop']);
        $router->post('/admin/media/crop/reset', [MediaController::class, 'resetCrop']);
        $router->post('/admin/media/delete', [MediaController::class, 'delete']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Médias', 'url' => '/admin/media', 'permission' => 'media.upload'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_media.sql',
            __DIR__ . '/migrations/002_add_crop_to_media.sql',
            __DIR__ . '/migrations/003_create_media_folders.sql',
        ];
    }
}
