<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Filtrage géographique des accès à l'administration (brief §6).
 *
 * Détermine le pays d'une IP sans dépendance ni appel externe (RGPD), en
 * s'appuyant sur ce que l'environnement fournit déjà, par ordre de priorité :
 *   1. variables serveur posées par mod_geoip / mod_maxminddb (Apache) ou un CDN
 *      (ex. Cloudflare CF-IPCountry) ;
 *   2. l'extension PHP `geoip` si l'hébergeur l'expose ;
 *   3. une base MaxMind GeoLite2-Country embarquée (optionnelle, voir config).
 *
 * Conçu *fail-open* : si aucune source ne donne le pays, on n'enferme jamais
 * l'administrateur légitime dehors (configurable en fail-closed). Les IP privées
 * et de loopback (réseau interne, développement) sont toujours autorisées.
 *
 * Voir config/security.php.
 */
final class IpGeoGuard
{
    /**
     * Bloque la requête (403) si l'IP n'est pas autorisée géographiquement.
     * Appelé tôt dans le front controller de l'admin.
     */
    public static function enforce(?string $ip = null): void
    {
        $ip ??= self::clientIp();
        if (self::allows($ip)) {
            return;
        }
        self::reject();
    }

    /**
     * L'IP est-elle autorisée à atteindre l'administration ?
     */
    public static function allows(string $ip): bool
    {
        if (!(bool) Config::get('security.geo_block_enabled', true)) {
            return true;
        }

        // Réseau interne / loopback / IP réservées (dev) : toujours autorisés.
        if (!self::isPublicIp($ip)) {
            return true;
        }

        $country = self::countryOf($ip);
        if ($country === null) {
            // Aucune source de pays fiable : fail-open par défaut.
            return (bool) Config::get('security.geo_fail_open', true);
        }

        $allowed = array_map(
            static fn ($c): string => strtoupper((string) $c),
            (array) Config::get('security.geo_allowed_countries', ['FR'])
        );

        return in_array($country, $allowed, true);
    }

    /**
     * Code pays ISO alpha-2 (majuscules) de l'IP, ou null si indéterminable.
     */
    public static function countryOf(string $ip): ?string
    {
        // 1. Variables serveur (mod_geoip, mod_maxminddb) ou en-tête CDN.
        $serverKeys = [
            'GEOIP_COUNTRY_CODE',
            'GEOIP_COUNTRY_CODE_V6',
            'MM_COUNTRY_CODE',
            'HTTP_CF_IPCOUNTRY',      // Cloudflare
            'HTTP_X_COUNTRY_CODE',    // proxy générique
        ];
        foreach ($serverKeys as $key) {
            $code = self::normalize($_SERVER[$key] ?? null);
            if ($code !== null) {
                return $code;
            }
        }

        // 2. Extension PHP geoip (si disponible sur l'hébergement).
        if (function_exists('geoip_country_code_by_name')) {
            $code = self::normalize(@geoip_country_code_by_name($ip) ?: null);
            if ($code !== null) {
                return $code;
            }
        }

        // 3. Base GeoLite2 embarquée (optionnelle).
        return self::countryFromDatabase($ip);
    }

    /**
     * Lecture d'une base MaxMind GeoLite2-Country (.mmdb), si fichier + lecteur
     * disponibles. Renvoie null sinon — c'est une amélioration facultative.
     */
    private static function countryFromDatabase(string $ip): ?string
    {
        $path = Config::get('security.geo_database', null);
        if (!is_string($path) || $path === '' || !is_file($path)) {
            return null;
        }
        if (!class_exists(\MaxMind\Db\Reader::class)) {
            return null;
        }

        try {
            $reader = new \MaxMind\Db\Reader($path);
            $record = $reader->get($ip);
            $reader->close();

            $code = $record['country']['iso_code']
                ?? ($record['registered_country']['iso_code'] ?? null);

            return self::normalize(is_string($code) ? $code : null);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalise un code pays : 2 lettres majuscules, hors codes « inconnus »
     * ou anonymisants posés par certains CDN (XX, T1/Tor, A1/A2 anonymes…).
     */
    private static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $code = strtoupper(trim($value));
        if (preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            return null;
        }
        if (in_array($code, ['XX', 'T1', 'A1', 'A2', 'O1'], true)) {
            return null;
        }
        return $code;
    }

    /**
     * IP routable publiquement ? (exclut privées, réservées et loopback.)
     */
    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function reject(): never
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>403</title>'
            . '<h1>403 — Accès refusé</h1>'
            . '<p>L’accès à cette administration est restreint géographiquement.</p>';
        exit;
    }
}
