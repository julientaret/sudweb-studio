<?php

declare(strict_types=1);

namespace App\Modules\Pages\Blocks;

use App\Core\Block;

/**
 * Bloc titre (statique). data : { text, level (1|2|3) }.
 */
final class HeadingBlock implements Block
{
    public function type(): string
    {
        return 'heading';
    }

    public function render(array $data): string
    {
        $text = trim((string) ($data['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        $level = $this->normalizeLevel($data['level'] ?? 2);
        return "<h{$level}>" . e($text) . "</h{$level}>";
    }

    public function form(array $data): string
    {
        $text = (string) ($data['text'] ?? '');
        $level = $this->normalizeLevel($data['level'] ?? 2);

        $options = '';
        foreach ([1 => 'Titre 1', 2 => 'Titre 2', 3 => 'Titre 3'] as $value => $label) {
            $selected = $value === $level ? ' selected' : '';
            $options .= '<option value="' . $value . '"' . $selected . '>' . e($label) . '</option>';
        }

        return '<label>Texte du titre</label>'
            . '<input type="text" name="blocks[__KEY__][data][text]" value="' . e($text) . '">'
            . '<label>Niveau</label>'
            . '<select name="blocks[__KEY__][data][level]">' . $options . '</select>';
    }

    public function sanitize(array $input): array
    {
        return [
            'text'  => trim((string) ($input['text'] ?? '')),
            'level' => $this->normalizeLevel($input['level'] ?? 2),
        ];
    }

    private function normalizeLevel(mixed $level): int
    {
        $level = (int) $level;
        return in_array($level, [1, 2, 3], true) ? $level : 2;
    }
}
