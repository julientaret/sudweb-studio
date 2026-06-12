<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF (brief §6).
 *
 * Un token par session, injecté dans chaque formulaire et vérifié avec
 * hash_equals(). Tous les POST doivent passer par check().
 *
 * Suppose une session déjà démarrée (front controller).
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf';

    /**
     * Token de la session (généré une fois, persistant).
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Champ caché prêt à insérer dans un <form>.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /**
     * Vérifie un token soumis (comparaison constante).
     */
    public static function check(?string $token): bool
    {
        if (!is_string($token) || $token === '' || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }
}
