<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Config;
use App\Core\Kernel;

/**
 * Construction des URLs publiques d'un média (brief §5).
 *
 * Tout est DÉRIVÉ du filename — aucune URL n'est stockée. Régénérer les
 * déclinaisons ou changer de presets ne casse donc aucune référence.
 *
 * Le paramètre $bust ajoute un jeton de cache (mtime du fichier) : utile en
 * back-office après un recadrage, où le fichier change mais l'URL reste la même.
 */
final class MediaUrl
{
    /** URL de l'original (format d'origine, re-encodé proprement). */
    public static function original(Media $media, bool $bust = false): string
    {
        return self::bust(self::base() . '/' . $media->filename, $media->filename, $bust);
    }

    /** URL de la version WebP pleine taille. */
    public static function webp(Media $media, bool $bust = false): string
    {
        return self::variantUrl($media, '', $bust);
    }

    /**
     * URL d'une déclinaison WebP (ex. "thumb", "medium", "large").
     * Retombe sur le WebP pleine taille si le preset est inconnu.
     */
    public static function size(Media $media, string $size, bool $bust = false): string
    {
        $presets = (array) Config::get('media.sizes', []);
        if (!isset($presets[$size])) {
            return self::webp($media, $bust);
        }
        return self::variantUrl($media, '-' . $size, $bust);
    }

    private static function variantUrl(Media $media, string $suffix, bool $bust): string
    {
        $dir = $media->dir();
        $rel = ($dir !== '' ? $dir . '/' : '') . $media->base() . $suffix . '.webp';
        return self::bust(self::base() . '/' . $rel, $rel, $bust);
    }

    private static function bust(string $url, string $relPath, bool $bust): string
    {
        if (!$bust) {
            return $url;
        }
        $path = (string) Config::get('storage.uploads.path', '');
        $dir = $path !== ''
            ? rtrim($path, '/')
            : Kernel::instance()->basePath() . '/public/uploads';
        $abs = $dir . '/' . $relPath;
        $v = is_file($abs) ? filemtime($abs) : null;
        return $v ? $url . '?v=' . $v : $url;
    }

    /** Préfixe d'URL des uploads : storage.uploads.url, sinon {base_path}/uploads. */
    private static function base(): string
    {
        $url = (string) Config::get('storage.uploads.url', '');
        if ($url !== '') {
            return rtrim($url, '/');
        }
        return rtrim((string) Config::get('app.base_path', ''), '/') . '/uploads';
    }
}
