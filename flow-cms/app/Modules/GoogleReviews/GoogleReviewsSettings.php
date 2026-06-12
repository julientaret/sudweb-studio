<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews;

use App\Core\Kernel;

/**
 * État du module choisi/produit depuis l'admin, stocké dans
 * storage/google_reviews.json — même approche que storage/modules.json : un
 * état d'admin, pas une constante serveur. Deux choses y vivent :
 *  - l'établissement suivi (place_id + nom + adresse pour réaffichage),
 *    choisi via la recherche Find Place ;
 *  - l'horodatage de la dernière synchro, qui porte le verrou « une synchro
 *    par jour » (limite le coût d'API et les clics compulsifs).
 */
final class GoogleReviewsSettings
{
    private string $path;

    public function __construct()
    {
        $this->path = Kernel::instance()->basePath() . '/storage/google_reviews.json';
    }

    /** @return array{place_id: string, name: string, address: string}|null */
    public function place(): ?array
    {
        $data = $this->read();
        if (!is_string($data['place_id'] ?? null) || $data['place_id'] === '') {
            return null;
        }
        return [
            'place_id' => $data['place_id'],
            'name'     => (string) ($data['name'] ?? ''),
            'address'  => (string) ($data['address'] ?? ''),
        ];
    }

    public function placeId(): string
    {
        return $this->place()['place_id'] ?? '';
    }

    public function savePlace(string $placeId, string $name, string $address): void
    {
        $this->write(['place_id' => $placeId, 'name' => $name, 'address' => $address] + $this->read());
    }

    /** Horodatage (Y-m-d H:i:s) de la dernière synchro lancée, ou null. */
    public function lastSyncAt(): ?string
    {
        $val = $this->read()['last_sync'] ?? null;
        return is_string($val) && $val !== '' ? $val : null;
    }

    /** Une synchro a-t-elle déjà été lancée aujourd'hui ? (verrou 1/jour) */
    public function syncedToday(): bool
    {
        $last = $this->lastSyncAt();
        return $last !== null && substr($last, 0, 10) === date('Y-m-d');
    }

    public function markSynced(): void
    {
        $this->write(['last_sync' => date('Y-m-d H:i:s')] + $this->read());
    }

    /** Lève le verrou (appelé par la purge : repartir de zéro sans attendre demain). */
    public function clearLastSync(): void
    {
        $data = $this->read();
        unset($data['last_sync']);
        $this->write($data);
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
