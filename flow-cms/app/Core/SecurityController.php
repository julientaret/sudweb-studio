<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Actions de sécurité du back-office (brief §6).
 *
 * Pour l'instant : acquittement du bandeau d'alerte « connexion depuis une IP
 * inhabituelle » posé en session par Core\Auth lors de la connexion.
 */
final class SecurityController extends Controller
{
    /** Clé de session du drapeau d'alerte (partagée avec Auth et le layout). */
    public const IP_ALERT_KEY = 'security_ip_alert';

    /**
     * Masque le bandeau d'alerte : retire le drapeau de session puis revient à la
     * page courante (referer interne) ou, à défaut, au tableau de bord.
     */
    public function dismissIpAlert(): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        unset($_SESSION[self::IP_ALERT_KEY]);

        $ref  = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $path = parse_url($ref, PHP_URL_PATH);
        $base = $this->basePath();
        if (is_string($path) && $path !== '' && ($base === '' || str_starts_with($path, $base))) {
            header('Location: ' . $path);
            exit;
        }
        $this->redirect('/admin');
    }
}
