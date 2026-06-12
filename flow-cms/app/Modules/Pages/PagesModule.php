<?php

declare(strict_types=1);

namespace App\Modules\Pages;

use App\Core\Kernel;
use App\Core\Module;
use App\Core\Router;
use App\Modules\Pages\Blocks\HeadingBlock;
use App\Modules\Pages\Blocks\ImageBlock;
use App\Modules\Pages\Blocks\RichtextBlock;

/**
 * Module Pages — page-builder par blocs (brief §4).
 *
 * Permissions dans l'espace « content » (base commune aux modules de contenu,
 * brief §3/§7) : content.edit, content.publish. Voir docs/modules/pages.md.
 */
final class PagesModule implements Module
{
    /**
     * Libellés des blocs pour l'éditeur. Peut lister des blocs fournis par
     * d'autres modules : BlockContent filtre sur le registre, donc un bloc dont
     * le module est désactivé disparaît simplement de l'éditeur.
     */
    public const BLOCK_LABELS = [
        'heading'        => 'Titre',
        'richtext'       => 'Texte',
        'image'          => 'Image',
        'google_reviews' => 'Avis Google',
    ];

    public function name(): string
    {
        return 'pages';
    }

    public function boot(Router $router): void
    {
        // Enregistrement des blocs fournis (brief §4 : dans boot()).
        $blocks = Kernel::instance()->blocks();
        $blocks->register(new HeadingBlock());
        $blocks->register(new RichtextBlock());
        $blocks->register(new ImageBlock());

        // Back-office.
        $router->get('/admin/pages', [PageController::class, 'index']);
        $router->get('/admin/pages/create', [PageController::class, 'create']);
        $router->post('/admin/pages/create', [PageController::class, 'store']);
        $router->get('/admin/pages/edit', [PageController::class, 'edit']);
        $router->post('/admin/pages/edit', [PageController::class, 'update']);
        $router->post('/admin/pages/delete', [PageController::class, 'destroy']);

        // Front : /{slug} (les routes exactes admin restent prioritaires).
        $router->get('/{slug}', [PageController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'Pages', 'url' => '/admin/pages', 'permission' => 'content.edit'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_pages.sql',
        ];
    }
}
