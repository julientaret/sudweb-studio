<?php

declare(strict_types=1);

namespace App\Modules\Media\Blocks;

use App\Core\Block;
use App\Core\Config;
use App\Modules\Media\MediaRepository;
use App\Modules\Media\MediaUrl;

/**
 * Bloc image (dynamique) — référence un média par ID (brief §4/§5).
 *
 * Fourni par le module Media : il n'est enregistré (et donc proposé dans
 * l'éditeur) que lorsque Media est actif. Si Media est désactivé, le bloc
 * disparaît simplement des éditeurs des autres modules, qui restent utilisables.
 *
 * data : { media_id, alt, radius, shadow }. L'image n'est jamais référencée par
 * URL : on stocke l'ID, le chemin est reconstruit à l'affichage
 * (régénération/déplacement sûrs). radius/shadow pilotent des classes CSS
 * plutôt que du style inline, pour garder l'échappement simple et le rendu
 * cohérent avec la charte. Les classes img-radius-… et img-shadow-… sont
 * définies aux deux endroits qui peuvent rendre le front (thème
 * frontend/src/css/blocks.css ET gabarit de repli
 * Modules/Pages/views/front_layout.php) : les garder en phase.
 */
final class ImageBlock implements Block
{
    /** "md" = rendu d'origine (8px), pour ne pas changer visuellement les images déjà en place. */
    private const RADIUS_OPTIONS = ['none' => 'Aucun', 'sm' => 'Léger', 'md' => 'Moyen', 'lg' => 'Prononcé', 'full' => 'Complet'];
    private const SHADOW_OPTIONS = ['none' => 'Aucune', 'sm' => 'Légère', 'md' => 'Moyenne', 'lg' => 'Marquée'];

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

        // <picture> : srcset WebP responsive (mobile/tablet/desktop) — le
        // navigateur choisit la largeur selon l'écran via l'attribut `sizes`.
        // Repli sur l'original (format d'origine) pour les rares cas sans WebP.
        // width/height = dimensions du recadrage (ou de l'original) → pas de
        // saut de mise en page au chargement.
        [$w, $h] = MediaUrl::intrinsic($media);
        $sizesAttr = (string) Config::get('media.sizes_attr', '100vw');

        $radius = $this->normalizeChoice($data['radius'] ?? 'md', self::RADIUS_OPTIONS, 'md');
        $shadow = $this->normalizeChoice($data['shadow'] ?? 'none', self::SHADOW_OPTIONS, 'none');
        $imgClass = 'img-radius-' . $radius . ' img-shadow-' . $shadow;

        return '<figure class="block-image">'
            . '<picture>'
            . '<source type="image/webp" srcset="' . MediaUrl::srcset($media)
            . '" sizes="' . e($sizesAttr) . '">'
            . '<img src="' . e(MediaUrl::original($media)) . '" alt="' . e($alt) . '" class="' . e($imgClass) . '"'
            . ' width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async">'
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

        $radius = $this->normalizeChoice($data['radius'] ?? 'md', self::RADIUS_OPTIONS, 'md');
        $shadow = $this->normalizeChoice($data['shadow'] ?? 'none', self::SHADOW_OPTIONS, 'none');

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
            . '<input type="text" name="blocks[__KEY__][data][alt]" value="' . e($alt) . '">'
            . '<label>Coins arrondis</label>'
            . $this->picker('blocks[__KEY__][data][radius]', self::RADIUS_OPTIONS, $radius, 'radius')
            . '<label>Ombre portée</label>'
            . $this->picker('blocks[__KEY__][data][shadow]', self::SHADOW_OPTIONS, $shadow, 'shadow');
    }

    public function sanitize(array $input): array
    {
        return [
            'media_id' => max(0, (int) ($input['media_id'] ?? 0)),
            'alt'      => trim((string) ($input['alt'] ?? '')),
            'radius'   => $this->normalizeChoice($input['radius'] ?? 'md', self::RADIUS_OPTIONS, 'md'),
            'shadow'   => $this->normalizeChoice($input['shadow'] ?? 'none', self::SHADOW_OPTIONS, 'none'),
        ];
    }

    /**
     * Sélecteur en vignettes (radios cachées + aperçu visuel de l'effet), plus
     * parlant qu'un <select> pour un réglage visuel (cf. .effect-picker).
     *
     * @param array<string, string> $options
     */
    private function picker(string $name, array $options, string $selected, string $prefix): string
    {
        $html = '<div class="effect-picker" role="radiogroup">';
        foreach ($options as $value => $label) {
            $checked = $value === $selected ? ' checked' : '';
            $html .= '<label class="effect-swatch">'
                . '<input type="radio" name="' . e($name) . '" value="' . e($value) . '"' . $checked . '>'
                . '<span class="swatch-box swatch-' . e($prefix) . '-' . e($value) . '"></span>'
                . '<span class="swatch-name">' . e($label) . '</span>'
                . '</label>';
        }
        return $html . '</div>';
    }

    /**
     * @param array<string, string> $options
     */
    private function normalizeChoice(mixed $value, array $options, string $default): string
    {
        $value = (string) $value;
        return array_key_exists($value, $options) ? $value : $default;
    }
}
