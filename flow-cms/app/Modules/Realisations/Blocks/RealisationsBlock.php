<?php

declare(strict_types=1);

namespace App\Modules\Realisations\Blocks;

use App\Core\Block;
use App\Core\Config;
use App\Core\DeltaRenderer;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;
use App\Modules\Realisations\Realisation;
use App\Modules\Realisations\RealisationRepository;

/**
 * Bloc front « Réalisations » (dynamique, brief §4) : son render() requête la
 * base en live et rend les réalisations publiées, dans l'ordre des positions.
 *
 * data : { title: string, limit: int (1..30, défaut 6) }.
 * Markup volontairement neutre (classes realisations-*) : le style vit dans le
 * thème. Chaque réalisation porte sa photo (référencée par ID média), son titre
 * et sa description rich text (Delta rendu par DeltaRenderer).
 */
final class RealisationsBlock implements Block
{
    private const DEFAULT_LIMIT = 6;
    private const MAX_LIMIT = 30;

    public function type(): string
    {
        return 'realisations';
    }

    public function label(): string
    {
        return 'Réalisations';
    }

    public function render(array $data): string
    {
        $limit = $this->normalizeLimit($data['limit'] ?? self::DEFAULT_LIMIT);
        $items = (new RealisationRepository())->published($limit);
        if ($items === []) {
            return '';
        }

        $title = trim((string) ($data['title'] ?? ''));
        $heading = $title !== ''
            ? '<h2 class="realisations__title">' . e($title) . '</h2>'
            : '';

        $cards = '';
        foreach ($items as $item) {
            $cards .= $this->renderCard($item);
        }

        return '<section class="realisations">'
            . $heading
            . '<ul class="realisations__list">' . $cards . '</ul>'
            . '</section>';
    }

    public function form(array $data): string
    {
        $title = (string) ($data['title'] ?? '');
        $limit = $this->normalizeLimit($data['limit'] ?? self::DEFAULT_LIMIT);

        return '<label>Titre de la section (optionnel)</label>'
            . '<input type="text" name="blocks[__KEY__][data][title]" value="' . e($title) . '"'
            . ' placeholder="Nos réalisations">'
            . '<label>Nombre de réalisations affichées</label>'
            . '<input type="number" name="blocks[__KEY__][data][limit]" value="' . $limit . '"'
            . ' min="1" max="' . self::MAX_LIMIT . '">';
    }

    public function sanitize(array $input): array
    {
        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'limit' => $this->normalizeLimit($input['limit'] ?? self::DEFAULT_LIMIT),
        ];
    }

    private function renderCard(Realisation $item): string
    {
        $photo = $this->renderPhoto($item->mediaId, $item->title);
        $body = DeltaRenderer::toHtml($item->description);

        return '<li class="realisations__item">'
            . $photo
            . '<h3 class="realisations__item-title">' . e($item->title) . '</h3>'
            . ($body !== '' ? '<div class="realisations__item-text rte">' . $body . '</div>' : '')
            . '</li>';
    }

    /**
     * <picture> responsive WebP (srcset mobile/tablet/desktop, repli original),
     * ou chaîne vide si pas de photo / Media désactivé. Calqué sur le bloc image.
     */
    private function renderPhoto(?int $mediaId, string $alt): string
    {
        if ($mediaId === null || !module_enabled('Media')) {
            return '';
        }
        $media = (new MediaRepository())->find($mediaId);
        if ($media === null) {
            return '';
        }

        [$w, $h] = MediaUrl::intrinsic($media);
        $sizesAttr = (string) Config::get('media.sizes_attr', '100vw');

        return '<figure class="realisations__item-media">'
            . '<picture>'
            . '<source type="image/webp" srcset="' . MediaUrl::srcset($media)
            . '" sizes="' . e($sizesAttr) . '">'
            . '<img src="' . e(MediaUrl::original($media)) . '" alt="' . e($alt) . '"'
            . ' width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async">'
            . '</picture>'
            . '</figure>';
    }

    private function normalizeLimit(mixed $limit): int
    {
        $limit = (int) $limit;
        return $limit >= 1 && $limit <= self::MAX_LIMIT ? $limit : self::DEFAULT_LIMIT;
    }
}
