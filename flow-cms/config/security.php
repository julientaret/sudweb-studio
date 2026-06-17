<?php

declare(strict_types=1);

/**
 * Sécurité du back-office (brief §6).
 *
 * Filtrage géographique des accès à l'administration. Le pays de l'IP est
 * déterminé sans dépendance ni appel externe (RGPD) : on lit ce que l'infra
 * fournit déjà (mod_geoip/mod_maxminddb d'Apache, en-tête d'un CDN), l'extension
 * PHP geoip si présente, ou une base MaxMind GeoLite2 embarquée (optionnelle).
 *
 * Voir App\Core\IpGeoGuard.
 */

return [
    // Active le filtrage géographique de l'admin. Sans source de pays disponible,
    // le comportement dépend de 'geo_fail_open' ci-dessous.
    'geo_block_enabled' => true,

    // Codes pays ISO 3166-1 alpha-2 autorisés (majuscules). Par défaut : France.
    // Les IP privées/loopback (réseau interne, dev) sont toujours autorisées.
    'geo_allowed_countries' => ['FR'],

    // Politique quand AUCUNE source ne donne le pays (hébergement sans géo-IP) :
    //   true  → on laisse passer (recommandé : ne jamais enfermer l'admin dehors) ;
    //   false → on bloque (à n'activer qu'une fois une source de pays confirmée).
    'geo_fail_open' => true,

    // Chemin disque absolu d'une base MaxMind GeoLite2-Country (.mmdb), optionnelle.
    // Utilisée seulement si le fichier existe ET qu'un lecteur MaxMind\Db\Reader
    // est disponible. Laisser null pour s'appuyer uniquement sur l'infra/extension.
    'geo_database' => null,
];
