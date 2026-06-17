<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Configuration générale de l'application.
 */

// URL absolue du site public (frontend), depuis .env.local — ex.
// https://www.monsite.com (sans slash final). Sert de base aux URLs absolues :
// médias et documents (voir MediaStorage/DocumentStorage), liens dans les mails…
// Vide → les URLs médias retombent sur le chemin auto-détecté ({base_path}/uploads).
$appUrl = rtrim((string) Config::env('APP_URL', (string) Config::env('HTTP', '')), '/');

return [
    // Nom du site, affiché sur le front public.
    'site_name' => 'Flow',

    // Nom du cookie de session (brief §6 : nom custom).
    'session_name' => 'flow_session',

    // Durée du cookie de session en secondes (0 = jusqu'à fermeture du navigateur).
    'session_lifetime' => 0,

    // Préfixe d'URL de l'application.
    //   null  → auto-détecté (racine d'un vhost OU sous-dossier type /flow-cms) ;
    //   "..."  → forcé (ex. "/flow-cms") si l'auto-détection ne convient pas.
    'base_path' => null,

    // URL absolue du site public (APP_URL). Base des URLs médias/documents et
    // des liens absolus. Vide en l'absence de config.
    'url' => $appUrl,

    // Alias historique de 'url' pour les liens absolus (mails…).
    'base_url' => $appUrl !== '' ? $appUrl : 'http://localhost/',

    // Anti-brute-force (brief §6) : seuil et fenêtre glissante (secondes).
    // Au-delà du seuil (par IP ou par e-mail), la connexion est verrouillée pour
    // la durée de la fenêtre et le support est alerté par e-mail.
    'login_max_attempts'   => 5,
    'login_lockout_window' => 600, // 10 minutes
];
