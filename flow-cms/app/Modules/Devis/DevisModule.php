<?php

declare(strict_types=1);

namespace App\Modules\Devis;

use App\Core\Config;
use App\Core\Module;
use App\Core\Router;

/**
 * Module Devis — demande de devis multi-étapes (pop-up) sur le site public,
 * demandes conservées en base et gérées dans l'admin (brief §3).
 *
 * Front : le pop-up vit dans le pied de page du site public (footer.php) et
 * dialogue en AJAX avec deux routes enregistrées ici :
 *   - POST /devis/token   → jeton CSRF + horodatage anti-bot (démarre la session) ;
 *   - POST /devis/envoyer → réception et persistance de la demande (JSON).
 * Deux segments → pas de conflit avec le catch-all /{slug} des Pages.
 *
 * RGPD : consentement explicite tracé, données minimales, IP seulement hachée
 * (anti-abus), purge automatique au-delà du délai de conservation.
 *
 * Module indépendant : il porte sa propre session légère et son hachage d'IP
 * (pas de dépendance au module Contact).
 */
final class DevisModule implements Module
{
    public function name(): string
    {
        return 'devis';
    }

    public function boot(Router $router): void
    {
        // Back-office.
        $router->get('/admin/devis', [DevisController::class, 'index']);
        $router->get('/admin/devis/show', [DevisController::class, 'show']);
        $router->post('/admin/devis/status', [DevisController::class, 'updateStatus']);
        $router->post('/admin/devis/delete', [DevisController::class, 'destroy']);

        // Front : pop-up (AJAX). Le jeton est servi en POST : il pose un état de
        // session (horodatage anti-bot) et reste ainsi hors de la mesure d'audience
        // (le module Stats ne compte que les GET).
        $router->post('/devis/token', [DevisController::class, 'token']);
        $router->post('/devis/envoyer', [DevisController::class, 'submit']);
    }

    public function adminMenu(): array
    {
        $label = 'Demandes de devis';
        // Pastille « non lues » — best-effort (table peut ne pas exister avant migration).
        try {
            $new = (new DevisRepository())->countNew();
            if ($new > 0) {
                $label .= ' (' . $new . ')';
            }
        } catch (\Throwable) {
            // silencieux : menu rendu sans pastille
        }

        return [
            ['label' => $label, 'url' => '/admin/devis', 'permission' => 'devis.view'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_quote_requests.sql',
        ];
    }

    /**
     * Démarre (au besoin) la session légère du pop-up de devis avec des cookies
     * durcis (HttpOnly, SameSite=Lax, Secure si HTTPS) — le site public étant par
     * ailleurs sans état. Identique côté token et côté réception.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name((string) Config::get('app.session_name', 'flow_session'));
        session_start();
    }

    /**
     * Empreinte d'une IP pour l'anti-abus (jamais d'IP en clair en base).
     * Secret côté serveur : APP_SECRET si défini, sinon dérivé de la config DB.
     */
    public static function hashIp(string $ip): string
    {
        $secret = (string) Config::env('APP_SECRET', '');
        if ($secret === '') {
            $secret = (string) Config::env('DB_NAME', '') . '|' . (string) Config::env('DB_HOST', '');
        }
        return hash('sha256', $ip . '|' . $secret);
    }
}
