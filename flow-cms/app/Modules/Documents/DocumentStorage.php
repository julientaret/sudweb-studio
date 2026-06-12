<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Config;

/**
 * Emplacement des documents : chemin disque et base d'URL.
 *
 * Pendant « documents » de Media\MediaStorage. Source unique pour
 * DocumentProcessor (écriture) et DocumentUrl (lien public).
 *
 * Par défaut, les documents vivent dans le site public (frontend/public/documents)
 * et sont servis à la base d'URL du site public (app.public_base_path) — la même
 * depuis l'admin et depuis le front, pour qu'un lien copié dans une page ne casse
 * jamais. On peut déplacer le stockage via .env.local (DOCUMENTS_PATH /
 * DOCUMENTS_URL, voir config/storage.php).
 */
final class DocumentStorage
{
    /** Dossier disque des documents (sans slash final). */
    public static function diskDir(): string
    {
        $path = (string) Config::get('storage.documents.path', '');
        if ($path !== '') {
            return rtrim($path, '/');
        }
        return rtrim((string) Config::get('storage.public_dir', ''), '/') . '/documents';
    }

    /** Préfixe d'URL des documents (sans slash final). */
    public static function baseUrl(): string
    {
        $url = (string) Config::get('storage.documents.url', '');
        if ($url !== '') {
            return rtrim($url, '/');
        }
        return rtrim((string) Config::get('app.public_base_path', ''), '/') . '/documents';
    }
}
