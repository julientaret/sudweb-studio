<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mode démonstration — état piloté depuis l'onglet Administration, stocké dans
 * storage/demo.json (même approche que storage/maintenance.json : un état
 * d'admin, pas une constante serveur).
 *
 * Ne porte que l'état (actif + id du compte démo) ; la création/suppression du
 * compte et des données factices vit dans App\Core\DemoSeeder.
 */
final class DemoSettings
{
    private string $path;

    public function __construct(string $basePath)
    {
        $this->path = rtrim($basePath, '/') . '/storage/demo.json';
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->read()['enabled'] ?? false);
    }

    public function setEnabled(bool $enabled): void
    {
        $this->write(['enabled' => $enabled] + $this->read());
    }

    public function userId(): ?int
    {
        $value = $this->read()['user_id'] ?? null;
        return $value !== null ? (int) $value : null;
    }

    public function setUserId(?int $id): void
    {
        $this->write(['user_id' => $id] + $this->read());
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
