<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rendu d'un Delta Quill en HTML, côté serveur (brief §8).
 *
 * Infrastructure de contenu partagée (niveau Core) : réutilisable par tout module
 * qui édite du texte riche (Pages, Actualités, Agenda, Pages légales…) sans créer
 * de dépendance inter-modules.
 *
 * On stocke le Delta (JSON), pas du HTML brut. Le HTML est produit ici en
 * n'émettant QUE des balises autorisées (prose bornée) : pas de HTML arbitraire
 * à purifier → pas de HTML Purifier → zéro-dépendance préservé.
 *
 * Autorisé : gras, italique, souligné, barré, couleur (palette restreinte),
 * liens (h/s/mailto), titres h2/h3, listes ordonnées et à puces, citation,
 * bloc de code et alignement. Tout le reste est ignoré. Le texte est
 * systématiquement échappé.
 */
final class DeltaRenderer
{
    /**
     * @param array<string, mixed> $delta structure {"ops": [...]}
     */
    public static function toHtml(array $delta): string
    {
        $ops = $delta['ops'] ?? null;
        if (!is_array($ops)) {
            return '';
        }

        $lines = self::splitLines($ops);
        return self::renderLines($lines);
    }

    /**
     * Découpe les ops en lignes : chaque ligne porte ses segments inline et les
     * attributs de bloc (titre/liste) issus du saut de ligne qui la termine.
     *
     * @param array<int, mixed> $ops
     * @return array<int, array{segments: array<int, array{text:string, attrs:array<string,mixed>}>, block: array<string,mixed>}>
     */
    private static function splitLines(array $ops): array
    {
        $lines = [];
        $current = [];

        foreach ($ops as $op) {
            if (!is_array($op) || !isset($op['insert']) || !is_string($op['insert'])) {
                continue; // embeds (images…) hors du périmètre prose
            }
            $attrs = is_array($op['attributes'] ?? null) ? $op['attributes'] : [];
            $parts = explode("\n", $op['insert']);
            $last = count($parts) - 1;

            foreach ($parts as $i => $text) {
                if ($i < $last) {
                    if ($text !== '') {
                        $current[] = ['text' => $text, 'attrs' => $attrs];
                    }
                    // L'attribut du "\n" définit le type de bloc de la ligne close.
                    $lines[] = ['segments' => $current, 'block' => $attrs];
                    $current = [];
                } elseif ($text !== '') {
                    $current[] = ['text' => $text, 'attrs' => $attrs];
                }
            }
        }
        if ($current !== []) {
            $lines[] = ['segments' => $current, 'block' => []];
        }
        return $lines;
    }

    /**
     * @param array<int, array{segments: array, block: array}> $lines
     */
    private static function renderLines(array $lines): string
    {
        $html = '';
        $openList = null;  // 'ol' | 'ul' | null
        $openCode = false; // dans un <pre> ?

        foreach ($lines as $line) {
            $block = $line['block'];
            $inline = self::renderSegments($line['segments']);
            $listType = $block['list'] ?? null;

            // --- Bloc de code : lignes consécutives regroupées dans un <pre> ---
            if (!empty($block['code-block'])) {
                if ($openList !== null) {
                    $html .= "</{$openList}>";
                    $openList = null;
                }
                $html .= $openCode ? "\n" : '<pre>';
                $openCode = true;
                $html .= $inline;
                continue;
            }
            if ($openCode) {
                $html .= '</pre>';
                $openCode = false;
            }

            // --- Listes ---
            if ($listType === 'ordered' || $listType === 'bullet') {
                $tag = $listType === 'ordered' ? 'ol' : 'ul';
                if ($openList !== $tag) {
                    if ($openList !== null) {
                        $html .= "</{$openList}>";
                    }
                    $html .= "<{$tag}>";
                    $openList = $tag;
                }
                $html .= '<li' . self::alignAttr($block) . ">{$inline}</li>";
                continue;
            }

            if ($openList !== null) {
                $html .= "</{$openList}>";
                $openList = null;
            }

            // --- Citation ---
            if (!empty($block['blockquote'])) {
                $html .= '<blockquote' . self::alignAttr($block) . ">{$inline}</blockquote>";
                continue;
            }

            // --- Titres / paragraphes ---
            $align = self::alignAttr($block);
            $header = $block['header'] ?? null;
            if ($header === 2) {
                $html .= "<h2{$align}>{$inline}</h2>";
            } elseif ($header === 3) {
                $html .= "<h3{$align}>{$inline}</h3>";
            } elseif (trim($inline) !== '') {
                $html .= "<p{$align}>{$inline}</p>";
            }
        }

        if ($openCode) {
            $html .= '</pre>';
        }
        if ($openList !== null) {
            $html .= "</{$openList}>";
        }
        return $html;
    }

    /**
     * @param array<int, array{text:string, attrs:array<string,mixed>}> $segments
     */
    private static function renderSegments(array $segments): string
    {
        $out = '';
        foreach ($segments as $seg) {
            $t = e($seg['text']);
            $a = $seg['attrs'];

            if (!empty($a['bold'])) {
                $t = "<strong>{$t}</strong>";
            }
            if (!empty($a['italic'])) {
                $t = "<em>{$t}</em>";
            }
            if (!empty($a['underline'])) {
                $t = "<u>{$t}</u>";
            }
            if (!empty($a['strike'])) {
                $t = "<s>{$t}</s>";
            }
            if (!empty($a['color']) && is_string($a['color'])) {
                $color = self::safeColor($a['color']);
                if ($color !== null) {
                    $t = '<span style="color:' . $color . '">' . $t . '</span>';
                }
            }
            if (!empty($a['link']) && is_string($a['link'])) {
                $href = self::safeUrl($a['link']);
                if ($href !== null) {
                    $t = '<a href="' . e($href) . '" rel="noopener nofollow">' . $t . '</a>';
                }
            }
            $out .= $t;
        }
        return $out;
    }

    /**
     * Palette de couleurs autorisée (doit rester alignée sur `palette`
     * côté pages-editor.js). Toute autre valeur est ignorée.
     */
    private const ALLOWED_COLORS = [
        '#1d4ed8', '#dc2626', '#16a34a', '#ea580c',
        '#ca8a04', '#9333ea', '#0891b2', '#374151',
    ];

    /**
     * Attribut style="text-align:…" pour un bloc, ou chaîne vide.
     * 'left' (défaut Quill) ne produit aucun attribut.
     */
    private static function alignAttr(array $block): string
    {
        $align = $block['align'] ?? null;
        if (in_array($align, ['center', 'right', 'justify'], true)) {
            return ' style="text-align:' . $align . '"';
        }
        return '';
    }

    /**
     * N'autorise qu'une couleur de la palette (whitelist stricte).
     */
    private static function safeColor(string $color): ?string
    {
        $color = strtolower(trim($color));
        return in_array($color, self::ALLOWED_COLORS, true) ? $color : null;
    }

    /**
     * N'autorise que des URLs sûres (http, https, mailto, ou lien interne /…).
     */
    private static function safeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url; // lien interne
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https', 'mailto'], true) ? $url : null;
    }
}
