<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Config;

/**
 * Emplacement des fichiers uploadés (médias) : chemin disque et base d'URL.
 *
 * Source unique pour MediaProcessor (écriture) et MediaUrl (lecture/URL), afin
 * que stockage et URL ne divergent jamais.
 *
 * Par défaut, les uploads vivent dans le site public (frontend/public/uploads)
 * et sont servis à la base d'URL du site public (app.public_base_path) — la même
 * depuis l'admin et depuis le front. On peut déplacer le stockage ailleurs via
 * .env.local (UPLOADS_PATH / UPLOADS_URL, voir config/storage.php).
 */
final class MediaStorage
{
    /** Dossier disque des uploads (sans slash final). */
    public static function diskDir(): string
    {
        $path = (string) Config::get('storage.uploads.path', '');
        if ($path !== '') {
            return rtrim($path, '/');
        }
        return rtrim((string) Config::get('storage.public_dir', ''), '/') . '/uploads';
    }

    /**
     * Préfixe d'URL des uploads (sans slash final). Trois paliers, par priorité :
     *   1. storage.uploads.url (UPLOADS_URL) — override explicite (CDN, autre domaine) ;
     *   2. app.url (APP_URL) — base absolue du site : URLs médias absolues et
     *      indépendantes du nom de dossier d'installation ;
     *   3. app.public_base_path — chemin auto-détecté ({base}/uploads), zéro config.
     */
    public static function baseUrl(): string
    {
        $url = (string) Config::get('storage.uploads.url', '');
        if ($url !== '') {
            return rtrim($url, '/');
        }
        $appUrl = (string) Config::get('app.url', '');
        if ($appUrl !== '') {
            return rtrim($appUrl, '/') . '/uploads';
        }
        return rtrim((string) Config::get('app.public_base_path', ''), '/') . '/uploads';
    }
}
