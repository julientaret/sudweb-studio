<?php

declare(strict_types=1);

namespace App\Modules\Contact;

use App\Core\Config;
use App\Core\Module;
use App\Core\Router;

/**
 * Module Contact — formulaire public + demandes conservées en base et gérées
 * dans l'admin (brief §3). Voir docs/modules/contact.md.
 *
 * Front : le formulaire vit dans une page statique du site public
 * (frontend/static/contact.php) ; il POSTe vers /contact/envoyer, route
 * enregistrée ici et traitée par ContactController::submit.
 *
 * RGPD : consentement explicite tracé, données minimales, IP seulement hachée
 * (anti-abus), purge automatique au-delà du délai de conservation.
 *
 * Aucune dépendance à un autre module (pas de blocs ni de média).
 */
final class ContactModule implements Module
{
    public function name(): string
    {
        return 'contact';
    }

    public function boot(Router $router): void
    {
        // Back-office.
        $router->get('/admin/contact', [ContactController::class, 'index']);
        $router->get('/admin/contact/show', [ContactController::class, 'show']);
        $router->post('/admin/contact/status', [ContactController::class, 'updateStatus']);
        $router->post('/admin/contact/delete', [ContactController::class, 'destroy']);

        // Front : réception du formulaire public (deux segments → pas de conflit
        // avec le catch-all /{slug} des Pages).
        $router->post('/contact/envoyer', [ContactController::class, 'submit']);
    }

    public function adminMenu(): array
    {
        $label = 'Demandes de contact';
        // Pastille « non lues » — best-effort (table peut ne pas exister avant migration).
        try {
            $new = (new ContactRepository())->countNew();
            if ($new > 0) {
                $label .= ' (' . $new . ')';
            }
        } catch (\Throwable) {
            // silencieux : menu rendu sans pastille
        }

        return [
            ['label' => $label, 'url' => '/admin/contact', 'permission' => 'contacts.view'],
        ];
    }

    public function migrations(): array
    {
        return [
            __DIR__ . '/migrations/001_create_contact_submissions.sql',
            __DIR__ . '/migrations/002_add_is_demo.sql',
        ];
    }

    /**
     * Démarre (au besoin) la session légère du formulaire de contact avec des
     * cookies durcis (HttpOnly, SameSite=Lax, Secure si HTTPS) — le site public
     * étant par ailleurs sans état. Utilisée par la page du formulaire et par le
     * contrôleur, pour des réglages identiques des deux côtés.
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
