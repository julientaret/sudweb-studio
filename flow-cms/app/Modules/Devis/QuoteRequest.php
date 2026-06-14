<?php

declare(strict_types=1);

namespace App\Modules\Devis;

/**
 * Entité « demande de devis » (module Devis).
 *
 * Données saisies via le pop-up multi-étapes du site public. `ipHash` est
 * l'empreinte de l'IP (anti-abus), jamais l'IP en clair.
 */
final class QuoteRequest
{
    public function __construct(
        public readonly int $id,
        public readonly string $projectType,
        public readonly string $budget,
        public readonly string $deadline,
        public readonly string $description,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $company,
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
        return new self(
            (int) $row['id'],
            (string) ($row['project_type'] ?? ''),
            (string) ($row['budget'] ?? ''),
            (string) ($row['deadline'] ?? ''),
            (string) ($row['description'] ?? ''),
            (string) $row['name'],
            (string) $row['email'],
            (string) ($row['phone'] ?? ''),
            (string) ($row['company'] ?? ''),
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
}
