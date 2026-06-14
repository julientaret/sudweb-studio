<?php

declare(strict_types=1);

namespace App\Modules\Media;

use App\Core\Config;

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
     * URL d'une déclinaison WebP (ex. "thumb", "mobile", "tablet", "desktop").
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

    /**
     * Dimensions affichées (= le recadrage s'il existe, sinon l'original).
     * Sert à poser width/height sur le <img> et à borner le srcset.
     *
     * @return array{0:int,1:int} [largeur, hauteur]
     */
    public static function intrinsic(Media $media): array
    {
        if ($media->hasCrop()) {
            return [(int) $media->cropW, (int) $media->cropH];
        }
        return [$media->width, $media->height];
    }

    /**
     * srcset responsive « url Nw, … » bâti depuis media.srcset_widths.
     *
     * En mode fit on n'agrandit jamais : chaque largeur est bornée à la source,
     * et les doublons sont écartés — une image plus petite que « desktop » ne
     * produit donc pas trois entrées de même largeur. La chaîne renvoyée est
     * DÉJÀ échappée (à injecter telle quelle dans l'attribut srcset).
     */
    public static function srcset(Media $media, bool $bust = false): string
    {
        [$srcW] = self::intrinsic($media);
        $presets = (array) Config::get('media.sizes', []);
        $entries = [];
        $seen = [];
        foreach ((array) Config::get('media.srcset_widths', []) as $name) {
            if (!isset($presets[$name])) {
                continue;
            }
            $target = (int) ($presets[$name]['w'] ?? 0);
            $real = $srcW > 0 ? min($target, $srcW) : $target;
            if ($real <= 0 || isset($seen[$real])) {
                continue;
            }
            $seen[$real] = true;
            $entries[] = e(self::size($media, $name, $bust)) . ' ' . $real . 'w';
        }
        return implode(', ', $entries);
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
        $abs = MediaStorage::diskDir() . '/' . $relPath;
        $v = is_file($abs) ? filemtime($abs) : null;
        return $v ? $url . '?v=' . $v : $url;
    }

    /** Préfixe d'URL des uploads (site public). Voir MediaStorage. */
    private static function base(): string
    {
        return MediaStorage::baseUrl();
    }
}
