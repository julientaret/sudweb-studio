<?php

declare(strict_types=1);

/**
 * Routes du site public (frontend) : pages statiques et redirections.
 *
 * Consommé par le front controller (frontend/public/index.php), AVANT la
 * résolution des pages dynamiques du CMS. Ordre de priorité :
 *   1. redirections déclarées ici (301/302) ;
 *   2. pages statiques : alias 'routes' ci-dessous, sinon convention
 *      frontend/static/<slug>.php ;
 *   3. pages dynamiques du CMS (par slug).
 *
 * Le « slug » est le chemin relatif à la racine du site, sans slash de bord
 * (ex. '' pour l'accueil, 'contact', 'infos/mentions-legales').
 */

return [

    /*
     * Alias explicites : slug d'URL => nom du fichier dans frontend/static/
     * (sans l'extension .php). À utiliser quand l'URL doit différer du nom de
     * fichier, ou simplement pour documenter les routes existantes.
     *
     * Note : un fichier frontend/static/<slug>.php est de toute façon servi par
     * convention même sans alias ici. Cette liste ne sert qu'aux cas URL ≠ fichier.
     */
    'routes' => [
        // 'a-propos'  => 'about',          // /a-propos  -> static/about.php
        // 'cgv'       => 'legal/cgv',      // /cgv       -> static/legal/cgv.php
    ],

    /*
     * Redirections : slug source => destination.
     *
     * Destination sous forme courte (chaîne) → 301 par défaut, ou détaillée
     * (['to' => ..., 'status' => 301|302]). Une destination commençant par '/'
     * est interne (préfixée par la base d'URL du site) ; une URL absolue
     * (http…) est laissée telle quelle. Statuts acceptés : 301, 302, 307, 308.
     */
    'redirects' => [
        // 'home'           => '/',                                   // 301 vers l'accueil
        // 'ancienne-page'  => ['to' => '/nouvelle-page', 'status' => 301],
        // 'partenaire'     => ['to' => 'https://exemple.com', 'status' => 302],
    ],

];
