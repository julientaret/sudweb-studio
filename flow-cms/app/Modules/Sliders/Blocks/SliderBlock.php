<?php

declare(strict_types=1);

namespace App\Modules\Sliders\Blocks;

use App\Core\Block;
use App\Core\Config;
use App\Core\DeltaRenderer;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;
use App\Modules\Sliders\Slide;
use App\Modules\Sliders\SlideRepository;

/**
 * Bloc front « Slider » (dynamique, brief §4) : son render() requête la base en
 * live et rend les diapositives publiées, dans l'ordre des positions.
 *
 * data : { title: string, limit: int (1..30, défaut 8) }.
 * Markup volontairement neutre (classes slider-*, attributs data-slider) : le
 * style ET le comportement de carrousel vivent dans le thème (aucun JS imposé
 * ici, cohérent avec la philosophie « le thème possède la présentation »).
 */
final class SliderBlock implements Block
{
    private const DEFAULT_LIMIT = 8;
    private const MAX_LIMIT = 30;

    public function type(): string
    {
        return 'slider';
    }

    public function label(): string
    {
        return 'Slider';
    }

    public function render(array $data): string
    {
        $limit = $this->normalizeLimit($data['limit'] ?? self::DEFAULT_LIMIT);
        $slides = (new SlideRepository())->published($limit);
        if ($slides === []) {
            return '';
        }

        $title = trim((string) ($data['title'] ?? ''));
        $heading = $title !== ''
            ? '<h2 class="slider__title">' . e($title) . '</h2>'
            : '';

        $items = '';
        foreach ($slides as $slide) {
            $items .= $this->renderSlide($slide);
        }

        return '<section class="slider" data-slider>'
            . $heading
            . '<ul class="slider__track">' . $items . '</ul>'
            . '</section>';
    }

    public function form(array $data): string
    {
        $title = (string) ($data['title'] ?? '');
        $limit = $this->normalizeLimit($data['limit'] ?? self::DEFAULT_LIMIT);

        return '<label>Titre de la section (optionnel)</label>'
            . '<input type="text" name="blocks[__KEY__][data][title]" value="' . e($title) . '"'
            . ' placeholder="">'
            . '<label>Nombre de diapositives affichées</label>'
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

    private function renderSlide(Slide $slide): string
    {
        $photo = $this->renderPhoto($slide->mediaId, $slide->title);
        $body = DeltaRenderer::toHtml($slide->description);

        return '<li class="slider__slide">'
            . $photo
            . '<div class="slider__caption">'
            . '<h3 class="slider__slide-title">' . e($slide->title) . '</h3>'
            . ($body !== '' ? '<div class="slider__slide-text rte">' . $body . '</div>' : '')
            . '</div>'
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

        return '<figure class="slider__slide-media">'
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
