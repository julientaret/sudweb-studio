<?php

declare(strict_types=1);

namespace App\Modules\Documents;

/**
 * Entité document.
 *
 * Porte l'enregistrement de la table `documents`. Le lien public est DÉRIVÉ du
 * `filename` (relatif à public/documents/), jamais stocké : voir DocumentUrl.
 */
final class Document
{
    public function __construct(
        public readonly int $id,
        public readonly string $filename,      // relatif à public/documents/, ex. "2026/06/rapport-ab12cd34.pdf"
        public readonly string $originalName,  // nom d'origine côté client (affiché)
        public readonly string $title,         // libellé éditorial réutilisable (facultatif)
        public readonly string $mime,
        public readonly string $ext,           // extension assainie (pdf, docx…)
        public readonly int $size,
        public readonly string $createdAt,
        public readonly ?int $folderId = null, // dossier de classement (null = « Non classé »)
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['filename'],
            (string) $row['original_name'],
            (string) ($row['title'] ?? ''),
            (string) $row['mime'],
            (string) ($row['ext'] ?? ''),
            (int) $row['size'],
            (string) $row['created_at'],
            isset($row['folder_id']) ? (int) $row['folder_id'] : null,
        );
    }

    /** Libellé d'affichage : le titre éditorial s'il existe, sinon le nom d'origine. */
    public function label(): string
    {
        return $this->title !== '' ? $this->title : $this->originalName;
    }
}
