<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews;

use App\Core\Kernel;

/**
 * État du module choisi/produit depuis l'admin, stocké dans
 * storage/google_reviews.json — même approche que storage/modules.json : un
 * état d'admin, pas une constante serveur. Y vivent :
 *  - l'établissement suivi (place_id + nom + adresse pour réaffichage),
 *    choisi via la recherche Find Place ;
 *  - l'horodatage de la dernière synchro, qui porte le verrou « une synchro
 *    par jour » (limite les clics compulsifs côté manager) ;
 *  - le quota mensuel de synchros (plafond de budget API, réglable par l'admin
 *    dans l'onglet) et son compteur, qui se remet à zéro au changement de mois.
 */
final class GoogleReviewsSettings
{
    /** Quota mensuel par défaut tant que l'admin n'en a pas réglé un autre. */
    public const DEFAULT_MONTHLY_QUOTA = 5;

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
        $data = $this->read();
        $month = date('Y-m');
        if (($data['sync_month'] ?? null) !== $month) {
            // Nouveau mois : le compteur de quota repart de zéro.
            $data['sync_month'] = $month;
            $data['sync_count'] = 0;
        }
        $data['sync_count'] = (int) ($data['sync_count'] ?? 0) + 1;
        $data['last_sync']  = date('Y-m-d H:i:s');
        $this->write($data);
    }

    /**
     * Quota mensuel de synchros réglé par l'admin. 0 = illimité. Par défaut
     * self::DEFAULT_MONTHLY_QUOTA tant qu'aucune valeur n'a été enregistrée.
     */
    public function monthlyQuota(): int
    {
        $val = $this->read()['monthly_quota'] ?? null;
        return is_int($val) && $val >= 0 ? $val : self::DEFAULT_MONTHLY_QUOTA;
    }

    public function setMonthlyQuota(int $quota): void
    {
        $data = $this->read();
        $data['monthly_quota'] = max(0, $quota);
        $this->write($data);
    }

    /** Nombre de synchros lancées durant le mois courant (0 si le mois a changé). */
    public function syncsThisMonth(): int
    {
        $data = $this->read();
        if (($data['sync_month'] ?? null) !== date('Y-m')) {
            return 0;
        }
        return (int) ($data['sync_count'] ?? 0);
    }

    /** Quota mensuel atteint ? (0 = illimité, jamais atteint). */
    public function quotaReached(): bool
    {
        $quota = $this->monthlyQuota();
        return $quota > 0 && $this->syncsThisMonth() >= $quota;
    }

    /** Synchros restantes ce mois-ci, ou null si le quota est illimité. */
    public function remainingThisMonth(): ?int
    {
        $quota = $this->monthlyQuota();
        return $quota === 0 ? null : max(0, $quota - $this->syncsThisMonth());
    }

    /**
     * Lève le verrou 1/jour (appelé par la purge : repartir de zéro sans
     * attendre demain). Ne touche PAS au compteur de quota mensuel : les appels
     * API déjà consommés ne se « remboursent » pas par une purge.
     */
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
