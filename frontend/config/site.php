<?php

declare(strict_types=1);

use App\Core\Config;

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
     * Aperçu des partages (Open Graph / Twitter Cards) : ce que montrent
     * Messenger, WhatsApp, LinkedIn, Slack ou X quand on colle un lien du site.
     *
     * 'image' est un chemin du site, rendu absolu au rendu (asset_abs_url()).
     * Le fichier est produit par `npm run build:brand` à partir de
     * frontend/src/brand/og-default.html — c'est là qu'on modifie la carte, pas
     * sur l'image. Ses dimensions sont déclarées pour que les robots affichent
     * la grande carte sans attendre le téléchargement de l'image.
     *
     * Une page peut la remplacer en posant $ogImage avant l'en-tête (chemin du
     * site ou URL absolue) — une réalisation peut ainsi partager son visuel.
     */
    'og' => [
        'image'  => '/assets/brand/og-default.png',
        'width'  => 1200,
        'height' => 630,
        'alt'    => 'Sud Web Studio — création de sites web à Villeneuve-sur-Lot : '
            . 'sites vitrines, web sur-mesure, reprise de site, référencement, hébergement.',
        'locale' => 'fr_FR',
    ],

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
        'Contact'      => '/contact',
    ],

    // Appel à l'action de l'en-tête et du menu mobile (bouton pêche).
    'cta' => [
        'label' => 'Discutons de votre projet',
        'url'   => '/devis',
    ],

    /*
     * Coordonnées affichées (pied de page, page de maintenance).
     *
     * L'adresse vient de EMAIL_CONTACT (.env.local), qui est déjà le
     * destinataire des formulaires de contact et de devis : une seule adresse à
     * tenir, et jamais d'écart entre celle qu'on affiche et celle qui reçoit.
     * Propre au serveur, elle peut donc différer entre le poste de dev et la
     * production. Vide → rien n'est affiché.
     */
    'contact' => [
        'city'    => 'Villeneuve-sur-Lot',
        'region'  => 'Lot-et-Garonne',
        'email'   => (string) Config::env('EMAIL_CONTACT', ''),
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
