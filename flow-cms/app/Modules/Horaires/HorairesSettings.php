<?php

declare(strict_types=1);

namespace App\Modules\Horaires;

use App\Core\Kernel;

/**
 * Horaires d'ouverture — état stocké dans storage/horaires.json (même approche
 * que storage/google_reviews.json : un état d'admin, pas une constante serveur).
 *
 * Deux ensembles de données :
 *  - weekly : les 7 jours de la semaine, chacun ouvert (avec une plage horaire)
 *    ou fermé ;
 *  - exceptions : fermetures ponctuelles (jour férié, congés…) qui priment sur
 *    les horaires hebdomadaires pour la période couverte. Affichage géré par
 *    App\Modules\Horaires\Blocks\OpeningHoursBlock, qui lit cet état en live.
 */
final class HorairesSettings
{
    /** Jours dans l'ordre d'affichage, clés ASCII stables (indépendantes du libellé). */
    public const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

    /** @var array<string, string> */
    public const DAY_LABELS = [
        'lundi'     => 'Lundi',
        'mardi'     => 'Mardi',
        'mercredi'  => 'Mercredi',
        'jeudi'     => 'Jeudi',
        'vendredi'  => 'Vendredi',
        'samedi'    => 'Samedi',
        'dimanche'  => 'Dimanche',
    ];

    public const MAX_EXCEPTIONS = 50;

    private string $path;

    public function __construct()
    {
        $this->path = Kernel::instance()->basePath() . '/storage/horaires.json';
    }

    /**
     * Horaires hebdomadaires, un enregistrement par jour (toujours les 7 jours,
     * dans l'ordre de self::DAYS). Valeurs par défaut tant que l'admin n'a rien
     * réglé : Lundi-Vendredi 8h30-18h00, Samedi 9h00-12h00, Dimanche fermé.
     *
     * @return array<string, array{closed: bool, open: string, close: string}>
     */
    public function weekly(): array
    {
        $stored = $this->read()['weekly'] ?? [];
        $stored = is_array($stored) ? $stored : [];

        $weekly = [];
        foreach (self::DAYS as $day) {
            $row = is_array($stored[$day] ?? null) ? $stored[$day] : null;
            $weekly[$day] = $row !== null
                ? [
                    'closed' => (bool) ($row['closed'] ?? false),
                    'open'   => $this->normalizeTime($row['open'] ?? ''),
                    'close'  => $this->normalizeTime($row['close'] ?? ''),
                ]
                : $this->defaultDay($day);
        }
        return $weekly;
    }

    /** @param array<string, array{closed?: mixed, open?: mixed, close?: mixed}> $days */
    public function setWeekly(array $days): void
    {
        $weekly = [];
        foreach (self::DAYS as $day) {
            $row = is_array($days[$day] ?? null) ? $days[$day] : [];
            $weekly[$day] = [
                'closed' => !empty($row['closed']),
                'open'   => $this->normalizeTime($row['open'] ?? ''),
                'close'  => $this->normalizeTime($row['close'] ?? ''),
            ];
        }
        $data = $this->read();
        $data['weekly'] = $weekly;
        $this->write($data);
    }

    /**
     * Fermetures exceptionnelles, triées par date de début.
     *
     * @return array<int, array{start: string, end: string, label: string}>
     */
    public function exceptions(): array
    {
        $stored = $this->read()['exceptions'] ?? [];
        $stored = is_array($stored) ? $stored : [];

        $exceptions = [];
        foreach ($stored as $row) {
            if (!is_array($row) || !isset($row['start']) || !$this->isValidDate((string) $row['start'])) {
                continue;
            }
            $start = (string) $row['start'];
            $end = isset($row['end']) && $this->isValidDate((string) $row['end']) ? (string) $row['end'] : $start;
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }
            $exceptions[] = [
                'start' => $start,
                'end'   => $end,
                'label' => trim((string) ($row['label'] ?? '')),
            ];
        }

        usort($exceptions, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);
        return $exceptions;
    }

    /** @param array<int, array{start?: mixed, end?: mixed, label?: mixed}> $exceptions */
    public function setExceptions(array $exceptions): void
    {
        $clean = [];
        foreach (array_slice($exceptions, 0, self::MAX_EXCEPTIONS) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $start = trim((string) ($row['start'] ?? ''));
            if (!$this->isValidDate($start)) {
                continue;
            }
            $end = trim((string) ($row['end'] ?? ''));
            $end = $this->isValidDate($end) ? $end : $start;
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }
            $clean[] = [
                'start' => $start,
                'end'   => $end,
                'label' => trim((string) ($row['label'] ?? '')),
            ];
        }

        usort($clean, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $data = $this->read();
        $data['exceptions'] = $clean;
        $this->write($data);
    }

    /** @return array{closed: bool, open: string, close: string} */
    private function defaultDay(string $day): array
    {
        return match ($day) {
            'samedi'   => ['closed' => false, 'open' => '09:00', 'close' => '12:00'],
            'dimanche' => ['closed' => true, 'open' => '', 'close' => ''],
            default    => ['closed' => false, 'open' => '08:30', 'close' => '18:00'],
        };
    }

    private function normalizeTime(mixed $value): string
    {
        $value = trim((string) $value);
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value : '';
    }

    private function isValidDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    /** @return array<string, mixed> */
    private function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->path), true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    private function write(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->path, $json . "\n", LOCK_EX);
    }
}
