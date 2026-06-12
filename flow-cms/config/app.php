<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Configuration générale de l'application.
 */
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

    // URL publique complète (depuis .env.local), utile pour les liens absolus (mails…).
    'base_url' => Config::env('HTTP', 'http://localhost/'),

    // Anti-brute-force (brief §6) : seuil et fenêtre glissante (secondes).
    'login_max_attempts'   => 5,
    'login_lockout_window' => 900, // 15 minutes
];
