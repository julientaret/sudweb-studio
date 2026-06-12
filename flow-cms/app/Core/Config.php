<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Configuration de Flow (brief §1 : zéro dépendance).
 *
 * Deux sources :
 *  - les variables d'environnement de .env.local (constantes propres au serveur) ;
 *  - les fichiers /config/*.php, chacun retournant un tableau associatif.
 *
 * Accès aux fichiers de config par clé pointée : Config::get('roles.admin').
 * Accès à l'environnement via Config::env('DB_HOST').
 */
final class Config
{
    /** @var array<string, string> */
    private static array $env = [];

    /** @var array<string, mixed> */
    private static array $store = [];

    private static bool $booted = false;

    /**
     * Charge .env.local puis tous les fichiers de /config.
     */
    public static function boot(string $basePath): void
    {
        if (self::$booted) {
            return;
        }

        self::loadEnv($basePath . '/.env.local');
        self::loadConfigDir($basePath . '/config');

        self::$booted = true;
    }

    /**
     * Parse un fichier .env minimaliste (KEY=VALUE, # commentaires).
     */
    private static function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Retire d'éventuels guillemets entourants.
            if (strlen($value) >= 2) {
                $first = $value[0];
                if (($first === '"' || $first === "'") && str_ends_with($value, $first)) {
                    $value = substr($value, 1, -1);
                }
            }
            self::$env[$key] = $value;
        }
    }

    private static function loadConfigDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            $data = require $file;
            if (is_array($data)) {
                self::$store[$name] = $data;
            }
        }
    }

    /**
     * Variable d'environnement (.env.local), avec valeur par défaut.
     */
    public static function env(string $key, ?string $default = null): ?string
    {
        return self::$env[$key] ?? $default;
    }

    public static function envBool(string $key, bool $default = false): bool
    {
        $value = self::$env[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Écrit une valeur de config par clé pointée (résolutions calculées au boot,
     * ex. le base_path auto-détecté).
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &self::$store;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
                return;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    /**
     * Valeur d'un fichier de config par clé pointée : Config::get('app.session_name').
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$store;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
