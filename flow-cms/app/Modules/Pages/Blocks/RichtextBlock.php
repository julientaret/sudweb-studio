<?php

declare(strict_types=1);

namespace App\Modules\Pages\Blocks;

use App\Core\Block;
use App\Modules\Pages\DeltaRenderer;

/**
 * Bloc texte riche (statique), édité avec Quill (brief §8).
 *
 * data : { delta: {ops:[...]} }. On stocke le Delta, jamais du HTML ; le rendu
 * front passe par le DeltaRenderer borné.
 */
final class RichtextBlock implements Block
{
    public function type(): string
    {
        return 'richtext';
    }

    public function render(array $data): string
    {
        $delta = $data['delta'] ?? null;
        if (!is_array($delta)) {
            return '';
        }
        $html = DeltaRenderer::toHtml($delta);
        return $html !== '' ? '<div class="rte">' . $html . '</div>' : '';
    }

    public function form(array $data): string
    {
        // Le Delta initial est passé au JS via un attribut data-*, qui instancie
        // Quill ; le champ caché reçoit le Delta sérialisé à la sauvegarde.
        $delta = $data['delta'] ?? ['ops' => []];
        $json = (string) json_encode($delta, JSON_UNESCAPED_UNICODE);

        return '<label>Texte</label>'
            . '<div class="quill-editor" id="quill-__KEY__" data-quill-input="quill-input-__KEY__"'
            . ' data-quill-delta="' . e($json) . '"></div>'
            . '<input type="hidden" id="quill-input-__KEY__" name="blocks[__KEY__][data][delta]"'
            . ' value="' . e($json) . '">';
    }

    public function sanitize(array $input): array
    {
        $raw = $input['delta'] ?? null;
        $delta = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);

        // On ne conserve que des ops dont l'insert est une chaîne (prose bornée).
        $ops = [];
        if (is_array($delta) && isset($delta['ops']) && is_array($delta['ops'])) {
            foreach ($delta['ops'] as $op) {
                if (is_array($op) && isset($op['insert']) && is_string($op['insert'])) {
                    $clean = ['insert' => $op['insert']];
                    if (isset($op['attributes']) && is_array($op['attributes'])) {
                        $clean['attributes'] = $op['attributes'];
                    }
                    $ops[] = $clean;
                }
            }
        }

        return ['delta' => ['ops' => $ops]];
    }
}
