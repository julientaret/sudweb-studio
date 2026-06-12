<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Config;

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

    /** Préfixe d'URL des documents : storage.documents.url, sinon {base_path}/documents. */
    private static function base(): string
    {
        $url = (string) Config::get('storage.documents.url', '');
        if ($url !== '') {
            return rtrim($url, '/');
        }
        return rtrim((string) Config::get('app.base_path', ''), '/') . '/documents';
    }
}
