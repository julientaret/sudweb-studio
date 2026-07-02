<?php

declare(strict_types=1);

namespace App\Modules\Media\Blocks;

use App\Core\Block;
use App\Core\Config;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;

/**
 * Bloc galerie (dynamique) — plusieurs images en grille, référencées par ID
 * (brief §4/§5). Fourni par le module Media, comme le bloc `image` : disparaît
 * simplement des éditeurs si Media est désactivé.
 *
 * data : { media_ids: int[] }. Sélection dans l'éditeur via la modale média
 * partagée, ajoutée une image à la fois (cf. pages-editor.js — addGalleryImage) ;
 * le champ caché porte les ID séparés par des virgules, dans l'ordre choisi.
 */
final class GalleryBlock implements Block
{
    private const MAX_IMAGES = 24;

    public function type(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return 'Galerie';
    }

    public function render(array $data): string
    {
        $ids = $this->normalizeIds($data['media_ids'] ?? []);
        if ($ids === []) {
            return '';
        }

        $repo = new MediaRepository();
        $sizesAttr = (string) Config::get('media.sizes_attr', '100vw');
        $html = '';
        foreach ($ids as $id) {
            $media = $repo->find($id);
            if ($media === null) {
                continue;
            }
            [$w, $h] = MediaUrl::intrinsic($media);
            $html .= '<figure>'
                . '<picture>'
                . '<source type="image/webp" srcset="' . MediaUrl::srcset($media) . '" sizes="' . e($sizesAttr) . '">'
                . '<img src="' . e(MediaUrl::original($media)) . '" alt="' . e($media->alt) . '"'
                . ' width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async">'
                . '</picture>'
                . '</figure>';
        }
        if ($html === '') {
            return '';
        }
        return '<div class="block-gallery">' . $html . '</div>';
    }

    public function form(array $data): string
    {
        $ids = $this->normalizeIds($data['media_ids'] ?? []);
        $repo = new MediaRepository();

        $thumbs = '';
        foreach ($ids as $id) {
            $media = $repo->find($id);
            if ($media === null) {
                continue;
            }
            $thumbs .= '<div class="gallery-thumb" data-id="' . e($id) . '">'
                . '<img src="' . e(MediaUrl::size($media, 'thumb')) . '" alt="">'
                . '<button type="button" class="gallery-remove" aria-label="Retirer cette image">×</button>'
                . '</div>';
        }

        return '<label>Images de la galerie</label>'
            . '<div class="gallery-field" data-input="gal-__KEY__">'
            . '<div class="gallery-grid">' . $thumbs . '</div>'
            . '<input type="hidden" id="gal-__KEY__" name="blocks[__KEY__][data][media_ids]" value="' . e(implode(',', $ids)) . '">'
            . '<button type="button" class="gallery-add">+ Ajouter une image</button>'
            . '</div>';
    }

    public function sanitize(array $input): array
    {
        return ['media_ids' => $this->normalizeIds($input['media_ids'] ?? [])];
    }

    /**
     * Accepte aussi bien une liste PHP (état déjà stocké) qu'une chaîne
     * "12,7,31" (champ caché du formulaire) — dédoublonne, garde l'ordre,
     * borne à MAX_IMAGES.
     *
     * @return array<int, int>
     */
    private function normalizeIds(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = array_filter(explode(',', $raw), static fn (string $v): bool => $v !== '');
        }
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
            if (count($ids) >= self::MAX_IMAGES) {
                break;
            }
        }
        return $ids;
    }
}
