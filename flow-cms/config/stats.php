<?php

declare(strict_types=1);

/**
 * Configuration du module Statistiques (mesure d'audience du site public).
 *
 * Tout est pensé privacy-first : aucune donnée ici ne sert au pistage, et la
 * collecte n'utilise ni cookie ni service tiers.
 */
return [
    // --- RGPD : limitation de la conservation -------------------------------
    // Les vues plus anciennes que ce délai sont purgées (bin/flow stats:purge,
    // déclenché aussi de façon opportuniste à l'ouverture du tableau de bord).
    // 13 mois = recommandation CNIL pour la mesure d'audience. 0 = pas de purge.
    'retention_months' => 13,

    // --- Exclusions ---------------------------------------------------------
    // Ne pas compter un membre de l'équipe connecté à l'admin qui navigue sur le
    // site public (détecté par la simple présence du cookie de session, sans en
    // poser ni en lire le contenu).
    'exclude_admins' => true,

    // IP à ne jamais comptabiliser (agence, bureau du client…). Comparées en
    // clair à REMOTE_ADDR — elles ne sont PAS stockées. Ex. : ['203.0.113.7'].
    'exclude_ips' => [],

    // Motifs de User-Agent considérés comme robots (recherche insensible à la
    // casse, sous-chaîne). Liste volontairement large mais éditable.
    'bot_user_agents' => [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit',
        'embedly', 'quora link preview', 'pinterest', 'bitlybot', 'redditbot',
        'whatsapp', 'telegrambot', 'discordbot', 'preview', 'fetch', 'monitor',
        'pingdom', 'uptime', 'headlesschrome', 'phantomjs', 'python-requests',
        'curl', 'wget', 'go-http-client', 'java/', 'okhttp', 'apache-httpclient',
        'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'petalbot', 'bytespider',
        'gptbot', 'ccbot', 'claudebot', 'google-extended', 'applebot',
    ],
];
