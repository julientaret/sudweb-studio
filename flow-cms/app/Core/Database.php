<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Accès base de données (brief §6 : PDO en requêtes préparées partout).
 *
 * Singleton minimal : une seule connexion PDO par requête HTTP / process CLI,
 * construite depuis .env.local. Aucune requête ne doit contourner prepare().
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Config::env('DB_HOST', '127.0.0.1');
        $name = Config::env('DB_NAME', '');
        $user = Config::env('DB_USER', 'root');
        $pass = Config::env('DB_PASS', '');

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Connexion base de données impossible : ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }
}
