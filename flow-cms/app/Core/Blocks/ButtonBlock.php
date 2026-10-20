<?php

declare(strict_types=1);

namespace App\Core\Blocks;

use App\Core\Block;

/**
 * Bloc bouton / CTA (statique). data : { text, url, new_tab }.
 *
 * Bloc de contenu de base (niveau Core) : toujours disponible pour tout module
 * qui utilise le page-builder, sans dépendance inter-modules.
 */
final class ButtonBlock implements Block
{
    public function type(): string
    {
        return 'button';
    }

    public function label(): string
    {
        return 'Bouton / CTA';
    }

    public function render(array $data): string
    {
        $text = trim((string) ($data['text'] ?? ''));
        $url = self::safeUrl((string) ($data['url'] ?? ''));
        if ($text === '' || $url === '') {
            return '';
        }

        $target = !empty($data['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';

        return '<a class="block-button" href="' . e($url) . '"' . $target . '>' . e($text) . '</a>';
    }

    public function form(array $data): string
    {
        $text = (string) ($data['text'] ?? '');
        $url = (string) ($data['url'] ?? '');
        $newTab = !empty($data['new_tab']);

        return '<label>Texte du bouton</label>'
            . '<input type="text" name="blocks[__KEY__][data][text]" value="' . e($text) . '">'
            . '<label>Lien (URL)</label>'
            . '<input type="text" name="blocks[__KEY__][data][url]" value="' . e($url) . '" placeholder="https://… ou /ma-page">'
            . '<label><input type="checkbox" name="blocks[__KEY__][data][new_tab]" value="1"' . ($newTab ? ' checked' : '') . '> Ouvrir dans un nouvel onglet</label>';
    }

    public function sanitize(array $input): array
    {
        return [
            'text'    => trim((string) ($input['text'] ?? '')),
            'url'     => self::safeUrl((string) ($input['url'] ?? '')),
            'new_tab' => !empty($input['new_tab']),
        ];
    }

    /**
     * Liste blanche des liens : internes (/page, page, ?q), ancres (#id),
     * http(s), mailto et tel. Tout le reste (javascript:, data:, vbscript:…,
     * y compris obfusqué par des caractères de contrôle) devient ''.
     * Appliqué aussi au rendu, pour les données enregistrées avant ce filtre.
     */
    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        // Les navigateurs ignorent tabulations/retours ligne dans un schéma
        // (« java\tscript: ») : on les refuse purement et simplement.
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return '';
        }
        // Un « // » ou « /\ » initial désigne un autre domaine, pas un lien interne.
        if (preg_match('#^[/\\\\]{2}#', $url)) {
            return '';
        }
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $m)) {
            return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true) ? $url : '';
        }
        return $url;
    }
}
