<?php

declare(strict_types=1);

/**
 * Paramètres du site public (frontend) — source unique pour l'en-tête, le pied
 * de page et les pages statiques. Lu via l'accesseur site() (frontend/includes/
 * helpers.php) : site('name'), site('nav'), site('footer')['copyright']…
 *
 * Centralise ici tout ce qui est « contenu de gabarit » : nom, navigation,
 * mentions du pied de page, liens sociaux, métas par défaut.
 */

return [

    // Identité du site.
    'name'    => 'Flow',
    'tagline' => '',

    // Méta description par défaut (surchargée par page via $description).
    'description' => '',

    // Langue du document (<html lang="…">).
    'lang' => 'fr',

    /*
     * Navigation principale : libellé => URL.
     * URL interne si elle commence par '/' (préfixée par la base du site),
     * sinon laissée telle quelle (lien externe).
     */
    'nav' => [
        // 'Accueil' => '/',
        // 'Contact' => '/contact',
    ],

    // Pied de page.
    'footer' => [
        'copyright' => 'Flow',   // affiché « © {année} {copyright} »
    ],

    /*
     * Liens sociaux : libellé => URL. Affichés dans le pied de page.
     */
    'social' => [
        // 'Instagram' => 'https://instagram.com/...',
    ],

];
