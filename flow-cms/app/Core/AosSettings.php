<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Durée par défaut des animations au scroll (AOS) — réglable depuis l'onglet
 * Administration, état stocké dans storage/aos.json (même approche que
 * storage/maintenance.json : un état d'admin, pas une constante serveur).
 *
 * Valeur transmise telle quelle à `AOS.init({ duration })` côté front (thème
 * frontend/ ET gabarit de repli Modules/Pages/views/front_layout.php, via un
 * attribut `data-aos-duration` porté par leur script — CSP oblige, pas de
 * script inline, cf. public/assets/js/aos-init.js).
 */
final class AosSettings
{
    public const DEFAULT_DURATION = 800;

    /** Valeurs proposées dans l'admin (multiples de 100, dans la plage usuelle AOS). */
    public const DURATION_OPTIONS = [
        200  => '200 ms',
        300  => '300 ms',
        400  => '400 ms',
        500  => '500 ms',
        600  => '600 ms',
        800  => '800 ms',
        1000 => '1000 ms',
        1200 => '1200 ms',
    ];

    private string $path;

    public function __construct(string $basePath)
    {
        $this->path = rtrim($basePath, '/') . '/storage/aos.json';
    }

    public function duration(): int
    {
        $value = (int) ($this->read()['duration'] ?? self::DEFAULT_DURATION);
        return array_key_exists($value, self::DURATION_OPTIONS) ? $value : self::DEFAULT_DURATION;
    }

    public function setDuration(int $duration): void
    {
        $duration = array_key_exists($duration, self::DURATION_OPTIONS) ? $duration : self::DEFAULT_DURATION;
        $this->write(['duration' => $duration]);
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
