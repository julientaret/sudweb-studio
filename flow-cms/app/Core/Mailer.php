<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Envoi d'e-mails (brief §1 : zéro dépendance — on s'appuie sur mail() de PHP).
 *
 * Minimal et défensif : en-têtes MIME pour du HTML, sujet encodé UTF-8, et
 * surtout neutralisation des injections d'en-têtes (CR/LF) sur toute adresse ou
 * valeur d'en-tête issue d'une saisie utilisateur (Reply-To du formulaire de
 * contact, par exemple).
 *
 * L'expéditeur (From) doit être un domaine maîtrisé par le serveur (EMAIL_SENDER)
 * pour passer SPF/DKIM ; l'adresse du visiteur va en Reply-To, jamais en From.
 */
final class Mailer
{
    /**
     * Envoie un e-mail HTML. Retourne false si l'envoi échoue (ou si mail()
     * n'est pas disponible) — l'appelant décide quoi en faire (le formulaire de
     * contact, lui, a déjà persisté la demande en base).
     *
     * @param array{from?: string, from_name?: string, reply_to?: string} $opts
     */
    public static function sendHtml(string $to, string $subject, string $html, array $opts = []): bool
    {
        if (!function_exists('mail')) {
            return false;
        }

        $to = self::sanitizeAddress($to);
        if ($to === null) {
            return false;
        }

        $from = self::sanitizeAddress($opts['from'] ?? (string) Config::env('EMAIL_SENDER', ''))
            ?? self::sanitizeAddress((string) Config::env('EMAIL_CONTACT', ''));
        if ($from === null) {
            return false;
        }

        $fromName = self::sanitizeHeaderValue($opts['from_name'] ?? (string) Config::get('app.site_name', 'Flow'));

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . self::formatFrom($fromName, $from),
        ];

        $replyTo = isset($opts['reply_to']) ? self::sanitizeAddress($opts['reply_to']) : null;
        if ($replyTo !== null) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode(self::sanitizeHeaderValue($subject)) . '?=';

        // -f fixe l'enveloppe (Return-Path) sur un domaine maîtrisé → meilleur SPF.
        return @mail($to, $encodedSubject, $html, implode("\r\n", $headers), '-f' . $from);
    }

    /**
     * Valide/normalise une adresse e-mail et bloque toute injection d'en-tête.
     */
    private static function sanitizeAddress(string $address): ?string
    {
        $address = trim(str_replace(["\r", "\n", "\0"], '', $address));
        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false ? $address : null;
    }

    /**
     * Retire les sauts de ligne d'une valeur d'en-tête (anti-injection).
     */
    private static function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
    }

    private static function formatFrom(string $name, string $address): string
    {
        $name = self::sanitizeHeaderValue($name);
        if ($name === '') {
            return $address;
        }
        // Nom encodé pour rester sûr avec les accents et les guillemets.
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $address . '>';
    }
}
