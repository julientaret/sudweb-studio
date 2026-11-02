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
    'name'    => 'Sud Web Studio',
    'tagline' => 'Studio de création web à Villeneuve-sur-Lot',

    // Méta description par défaut (surchargée par page via $description).
    'description' => 'Studio de création de sites web à Villeneuve-sur-Lot (47). '
        . 'Sites vitrines, sites sur mesure, référencement et web design pour les PME du Lot-et-Garonne.',

    // Langue du document (<html lang="…">).
    'lang' => 'fr',

    /*
     * Navigation principale : libellé => URL.
     * URL interne si elle commence par '/' (préfixée par la base du site),
     * sinon laissée telle quelle (lien externe).
     */
    'nav' => [
        'Accueil'      => '/',
        'Services'     => '/#expertises',
        'Réalisations' => '/realisations',
        'Tarifs'       => '/tarifs',
        'Contact'      => '/#contact',
    ],

    // Appel à l'action de l'en-tête et du menu mobile (bouton pêche).
    'cta' => [
        'label' => 'Discutons de votre projet',
        'url'   => '/#contact',
    ],

    // Coordonnées affichées (pied de page, contact, données structurées).
    'contact' => [
        'city'    => 'Villeneuve-sur-Lot',
        'region'  => 'Lot-et-Garonne',
        'email'   => 'contact@sudwebstudio.fr',
        'phone'   => '',
    ],

    // Pied de page.
    'footer' => [
        'copyright' => 'Sud Web Studio',   // affiché « © {année} {copyright} »
    ],

    /*
     * Liens sociaux : libellé => URL. Affichés dans le pied de page.
     */
    'social' => [
        // 'Instagram' => 'https://instagram.com/...',
        // 'LinkedIn'  => 'https://linkedin.com/company/...',
    ],

];
