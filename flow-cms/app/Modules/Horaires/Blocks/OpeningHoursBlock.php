<?php

declare(strict_types=1);

namespace App\Modules\Horaires\Blocks;

use App\Core\Block;
use App\Modules\Horaires\HorairesSettings;

/**
 * Bloc front « Horaires d'ouverture » (dynamique) : son render() lit
 * HorairesSettings en live, comme ReviewsBlock côté Avis Google. Les horaires
 * eux-mêmes se gèrent depuis l'écran dédié (/admin/horaires) ; le bloc ne
 * configure que le titre affiché, pour rester cohérent avec l'admin qui
 * possède la donnée.
 *
 * data : { title: string }.
 */
final class OpeningHoursBlock implements Block
{
    private const DEFAULT_TITLE = "Horaires d'ouverture";

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
        7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    public function type(): string
    {
        return 'horaires';
    }

    public function label(): string
    {
        return "Horaires d'ouverture";
    }

    public function render(array $data): string
    {
        $settings = new HorairesSettings();
        $title = e((string) ($data['title'] ?? self::DEFAULT_TITLE));

        $rows = '';
        foreach ($this->groupedDays($settings->weekly()) as $group) {
            $hours = $group['closed']
                ? '<strong class="block-hours__closed">Fermé</strong>'
                : '<strong>' . e($this->formatTime($group['open']) . ' – ' . $this->formatTime($group['close'])) . '</strong>';
            $rows .= '<li class="block-hours__row"><span>' . e($group['label']) . '</span>' . $hours . '</li>';
        }

        $exceptionsHtml = $this->renderExceptions($settings->exceptions());

        return '<div class="block-hours">'
            . '<h3 class="block-hours__title">' . $title . '</h3>'
            . '<ul class="block-hours__list">' . $rows . '</ul>'
            . $exceptionsHtml
            . '</div>';
    }

    public function form(array $data): string
    {
        $title = (string) ($data['title'] ?? '');

        return '<label>Titre de la section</label>'
            . '<input type="text" name="blocks[__KEY__][data][title]" value="' . e($title) . '"'
            . ' placeholder="' . e(self::DEFAULT_TITLE) . '">'
            . '<p class="hint">Les horaires se règlent depuis l\'écran « Horaires d\'ouverture » du menu admin.</p>';
    }

    public function sanitize(array $input): array
    {
        return [
            'title' => trim((string) ($input['title'] ?? '')),
        ];
    }

    /**
     * Fusionne les jours consécutifs ayant les mêmes horaires (ex. Lundi-Vendredi)
     * pour un affichage compact, comme sur les fiches d'établissement usuelles.
     *
     * @param array<string, array{closed: bool, open: string, close: string}> $weekly
     * @return array<int, array{label: string, closed: bool, open: string, close: string}>
     */
    private function groupedDays(array $weekly): array
    {
        $groups = [];
        foreach (HorairesSettings::DAYS as $day) {
            $row = $weekly[$day];
            $last = $groups === [] ? null : $groups[array_key_last($groups)];

            if ($last !== null && $last['closed'] === $row['closed']
                && $last['open'] === $row['open'] && $last['close'] === $row['close']
            ) {
                $lastKey = array_key_last($groups);
                $groups[$lastKey]['to'] = $day;
                continue;
            }

            $groups[] = [
                'from'   => $day,
                'to'     => $day,
                'closed' => $row['closed'],
                'open'   => $row['open'],
                'close'  => $row['close'],
            ];
        }

        return array_map(function (array $g): array {
            $label = $g['from'] === $g['to']
                ? HorairesSettings::DAY_LABELS[$g['from']]
                : HorairesSettings::DAY_LABELS[$g['from']] . ' – ' . HorairesSettings::DAY_LABELS[$g['to']];
            return [
                'label'  => $label,
                'closed' => $g['closed'],
                'open'   => $g['open'],
                'close'  => $g['close'],
            ];
        }, $groups);
    }

    /** @param array<int, array{start: string, end: string, label: string}> $exceptions */
    private function renderExceptions(array $exceptions): string
    {
        $today = date('Y-m-d');
        $items = '';
        foreach ($exceptions as $ex) {
            if ($ex['end'] < $today) {
                continue; // fermeture passée : plus pertinente à afficher.
            }
            $when = $ex['start'] === $ex['end']
                ? $this->formatDate($ex['start'])
                : $this->formatDate($ex['start']) . ' – ' . $this->formatDate($ex['end']);
            $label = $ex['label'] !== '' ? ' — ' . e($ex['label']) : '';
            $items .= '<li>' . e($when) . $label . '</li>';
        }

        if ($items === '') {
            return '';
        }

        return '<div class="block-hours__exceptions">'
            . '<p class="block-hours__exceptions-title">Fermetures exceptionnelles</p>'
            . '<ul>' . $items . '</ul>'
            . '</div>';
    }

    private function formatTime(string $hhmm): string
    {
        if (!preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $m)) {
            return '';
        }
        return ((int) $m[1]) . 'h' . $m[2];
    }

    private function formatDate(string $ymd): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $ymd);
        if ($d === false) {
            return $ymd;
        }
        return $d->format('j') . ' ' . self::MONTHS[(int) $d->format('n')] . ' ' . $d->format('Y');
    }
}
