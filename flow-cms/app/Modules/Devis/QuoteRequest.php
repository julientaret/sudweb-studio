<?php

declare(strict_types=1);

namespace App\Modules\Devis;

/**
 * Entité « demande de devis » (module Devis).
 *
 * Le formulaire étant désormais dynamique (étapes/questions éditables depuis
 * l'admin — voir DevisFormSettings), les réponses sont stockées sous forme d'une
 * liste ordonnée [{label, value}] dans `answers`. Seuls le nom et l'e-mail
 * gardent une colonne dédiée (liste admin, Reply-To). `ipHash` est l'empreinte
 * de l'IP (anti-abus), jamais l'IP en clair.
 */
final class QuoteRequest
{
    /**
     * @param array<int, array{label:string, value:string}> $answers
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly array $answers,
        public readonly bool $consent,
        public readonly string $consentVersion,
        public readonly ?string $ipHash,
        public readonly string $status,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $answers = [];
        if (isset($row['answers']) && is_string($row['answers']) && $row['answers'] !== '') {
            $decoded = json_decode((string) $row['answers'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $a) {
                    if (is_array($a) && isset($a['label'], $a['value'])) {
                        $answers[] = ['label' => (string) $a['label'], 'value' => (string) $a['value']];
                    }
                }
            }
        }

        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            $answers,
            (bool) $row['consent'],
            (string) ($row['consent_version'] ?? ''),
            isset($row['ip_hash']) && $row['ip_hash'] !== '' ? (string) $row['ip_hash'] : null,
            (string) $row['status'],
            (string) $row['created_at'],
        );
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    /** Premier élément de réponse (résumé pour la liste admin), ou chaîne vide. */
    public function summary(): string
    {
        return $this->answers[0]['value'] ?? '';
    }
}
