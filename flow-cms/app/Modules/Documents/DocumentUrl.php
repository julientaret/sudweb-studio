<?php

declare(strict_types=1);

namespace App\Modules\Documents;

/**
 * Construction du lien public d'un document.
 *
 * Tout est DÉRIVÉ du filename — aucune URL n'est stockée. C'est ce lien que
 * l'éditeur copie depuis la bibliothèque pour le réutiliser dans une page
 * (bouton « Copier le lien »).
 */
final class DocumentUrl
{
    /** Lien public du document (chemin absolu côté serveur, préfixé du base_path). */
    public static function public(Document $document): string
    {
        return self::base() . '/' . $document->filename;
    }

    /** Préfixe d'URL des documents (site public). Voir DocumentStorage. */
    private static function base(): string
    {
        return DocumentStorage::baseUrl();
    }
}
