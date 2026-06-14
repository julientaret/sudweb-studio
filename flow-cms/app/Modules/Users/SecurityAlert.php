<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Config;
use App\Core\Mailer;
use App\Core\View;

/**
 * Alertes de sécurité liées aux comptes (brief §6).
 *
 * Pour l'instant : notification d'une connexion réussie depuis une adresse IP
 * inhabituelle. Déclenchée par Core\Auth, qui détient l'utilisateur et l'IP au
 * bon moment. Best-effort : un échec d'envoi ne doit jamais bloquer la connexion.
 */
final class SecurityAlert
{
    /**
     * Destinataire des alertes de sécurité — le support de l'agence, codé en dur
     * volontairement : cette surveillance ne dépend pas de la config du site.
     */
    public const ALERT_RECIPIENT = 'csupport@sudweb-studio.com';

    /**
     * Notifie le support qu'un compte s'est connecté depuis une nouvelle IP.
     */
    public static function notifyNewIp(User $user, string $ip): void
    {
        $siteName = (string) Config::get('app.site_name', 'Flow');

        $view = new View([__DIR__ . '/views']);
        $html = $view->render('email_new_ip', [
            'siteName'  => $siteName,
            'userName'  => $user->name !== '' ? $user->name : $user->email,
            'userEmail' => $user->email,
            'ip'        => $ip,
            'date'      => date('d/m/Y à H:i'),
            'adminUrl'  => rtrim((string) Config::get('app.url', ''), '/'),
        ]);

        Mailer::sendHtml(
            self::ALERT_RECIPIENT,
            '[' . $siteName . '] Connexion admin depuis une nouvelle adresse IP',
            $html,
            ['from_name' => $siteName],
        );
    }
}
