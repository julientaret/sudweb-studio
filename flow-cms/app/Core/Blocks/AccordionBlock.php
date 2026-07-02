<?php

declare(strict_types=1);

namespace App\Core\Blocks;

use App\Core\Block;

/**
 * Bloc accordéon / FAQ (statique). data : { items: [ {q, a}, … jusqu'à MAX ] }.
 *
 * Bloc de contenu de base (niveau Core) : toujours disponible pour tout module
 * qui utilise le page-builder, sans dépendance inter-modules.
 *
 * Nombre d'éléments borné (pas de liste dynamique en JS) : cohérent avec les
 * autres blocs Core, qui restent de simples formulaires statiques. Un slot dont
 * la question est vide n'est simplement pas rendu — pas besoin de les compacter
 * à la sauvegarde, ce qui garde chaque slot stable d'une édition à l'autre.
 * Rendu en `<details>/<summary>` natifs : ouverture/fermeture sans JS.
 */
final class AccordionBlock implements Block
{
    private const MAX_ITEMS = 6;

    public function type(): string
    {
        return 'accordion';
    }

    public function label(): string
    {
        return 'Accordéons / Dépliants';
    }

    public function render(array $data): string
    {
        $items = $this->normalizeItems($data);
        $html = '';
        foreach ($items as $item) {
            if ($item['q'] === '') {
                continue;
            }
            $answer = nl2br(e($item['a']), false);
            $html .= '<details class="block-accordion-item">'
                . '<summary>' . e($item['q']) . '</summary>'
                . '<div class="block-accordion-a">' . $answer . '</div>'
                . '</details>';
        }
        if ($html === '') {
            return '';
        }
        return '<div class="block-accordion">' . $html . '</div>';
    }

    public function form(array $data): string
    {
        $items = $this->normalizeItems($data);
        $html = '';
        foreach ($items as $i => $item) {
            $n = $i + 1;
            $html .= '<label>Question ' . $n . '</label>'
                . '<input type="text" name="blocks[__KEY__][data][items][' . $i . '][q]" value="' . e($item['q']) . '">'
                . '<label>Réponse ' . $n . '</label>'
                . '<textarea name="blocks[__KEY__][data][items][' . $i . '][a]" rows="2">' . e($item['a']) . '</textarea>';
        }
        return $html;
    }

    public function sanitize(array $input): array
    {
        $rawItems = is_array($input['items'] ?? null) ? $input['items'] : [];
        $items = [];
        for ($i = 0; $i < self::MAX_ITEMS; $i++) {
            $raw = is_array($rawItems[$i] ?? null) ? $rawItems[$i] : [];
            $items[] = [
                'q' => trim((string) ($raw['q'] ?? '')),
                'a' => trim((string) ($raw['a'] ?? '')),
            ];
        }
        return ['items' => $items];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array{q:string,a:string}>
     */
    private function normalizeItems(array $data): array
    {
        $raw = is_array($data['items'] ?? null) ? $data['items'] : [];
        $items = [];
        for ($i = 0; $i < self::MAX_ITEMS; $i++) {
            $item = is_array($raw[$i] ?? null) ? $raw[$i] : [];
            $items[] = [
                'q' => trim((string) ($item['q'] ?? '')),
                'a' => trim((string) ($item['a'] ?? '')),
            ];
        }
        return $items;
    }
}
