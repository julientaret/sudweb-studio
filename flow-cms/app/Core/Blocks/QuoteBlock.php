<?php

declare(strict_types=1);

namespace App\Core\Blocks;

use App\Core\Block;

/**
 * Bloc citation (statique). data : { text, author (optionnel) }.
 *
 * Bloc de contenu de base (niveau Core) : toujours disponible pour tout module
 * qui utilise le page-builder, sans dépendance inter-modules.
 */
final class QuoteBlock implements Block
{
    public function type(): string
    {
        return 'quote';
    }

    public function label(): string
    {
        return 'Citation';
    }

    public function render(array $data): string
    {
        $text = trim((string) ($data['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        $author = trim((string) ($data['author'] ?? ''));

        return '<blockquote class="block-quote"><p>' . e($text) . '</p>'
            . ($author !== '' ? '<cite>' . e($author) . '</cite>' : '')
            . '</blockquote>';
    }

    public function form(array $data): string
    {
        $text = (string) ($data['text'] ?? '');
        $author = (string) ($data['author'] ?? '');

        return '<label>Texte de la citation</label>'
            . '<textarea name="blocks[__KEY__][data][text]" rows="3">' . e($text) . '</textarea>'
            . '<label>Auteur (optionnel)</label>'
            . '<input type="text" name="blocks[__KEY__][data][author]" value="' . e($author) . '">';
    }

    public function sanitize(array $input): array
    {
        return [
            'text'   => trim((string) ($input['text'] ?? '')),
            'author' => trim((string) ($input['author'] ?? '')),
        ];
    }
}
