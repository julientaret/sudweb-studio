<?php

declare(strict_types=1);

namespace App\Modules\Media\Blocks;

use App\Core\Block;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;

/**
 * Bloc image (dynamique) — référence un média par ID (brief §4/§5).
 *
 * Fourni par le module Media : il n'est enregistré (et donc proposé dans
 * l'éditeur) que lorsque Media est actif. Si Media est désactivé, le bloc
 * disparaît simplement des éditeurs des autres modules, qui restent utilisables.
 *
 * data : { media_id, alt }. L'image n'est jamais référencée par URL : on stocke
 * l'ID, le chemin est reconstruit à l'affichage (régénération/déplacement sûrs).
 */
final class ImageBlock implements Block
{
    public function type(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'Image';
    }

    public function render(array $data): string
    {
        $id = (int) ($data['media_id'] ?? 0);
        if ($id <= 0) {
            return '';
        }
        $media = (new MediaRepository())->find($id);
        if ($media === null) {
            return '';
        }

        $alt = trim((string) ($data['alt'] ?? '')) ?: $media->alt;

        // <picture> : WebP (medium) avec repli sur l'original (format d'origine).
        return '<figure class="block-image">'
            . '<picture>'
            . '<source type="image/webp" srcset="' . e(MediaUrl::size($media, 'medium')) . '">'
            . '<img src="' . e(MediaUrl::original($media)) . '" alt="' . e($alt) . '" loading="lazy">'
            . '</picture>'
            . '</figure>';
    }

    public function form(array $data): string
    {
        $selectedId = (int) ($data['media_id'] ?? 0);
        $alt = (string) ($data['alt'] ?? '');

        // Aperçu du média sélectionné (vignette). La sélection se fait via la
        // modale partagée de l'éditeur (cf. pages-editor.js) : pas de <select>.
        $media = $selectedId > 0 ? (new MediaRepository())->find($selectedId) : null;
        if ($media !== null) {
            $preview = '<div class="image-preview"><img src="'
                . e(MediaUrl::size($media, 'thumb')) . '" alt=""></div>';
        } else {
            $preview = '<div class="image-preview empty"><span class="hint">Aucune image</span></div>';
        }

        return '<label>Image (bibliothèque média)</label>'
            . '<div class="image-field" data-input="img-__KEY__">'
            . $preview
            . '<input type="hidden" id="img-__KEY__" name="blocks[__KEY__][data][media_id]"'
            . ' value="' . $selectedId . '">'
            . '<div class="image-field-actions">'
            . '<button type="button" class="image-choose">Choisir une image</button>'
            . '<button type="button" class="image-clear"' . ($media !== null ? '' : ' hidden')
            . '>Retirer</button>'
            . '</div>'
            . '</div>'
            . '<label>Texte alternatif (optionnel, sinon celui du média)</label>'
            . '<input type="text" name="blocks[__KEY__][data][alt]" value="' . e($alt) . '">';
    }

    public function sanitize(array $input): array
    {
        return [
            'media_id' => max(0, (int) ($input['media_id'] ?? 0)),
            'alt'      => trim((string) ($input['alt'] ?? '')),
        ];
    }
}
