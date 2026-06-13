<?php

declare(strict_types=1);

namespace App\Modules\Contact;

/**
 * Entité « demande de contact » (module Contact).
 *
 * Données minimales d'un message envoyé via le formulaire public. `ipHash` est
 * l'empreinte de l'IP (anti-abus), jamais l'IP en clair.
 */
final class ContactSubmission
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $message,
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
            (string) $row['name'],
            (string) $row['email'],
            (string) ($row['subject'] ?? ''),
            (string) $row['message'],
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
