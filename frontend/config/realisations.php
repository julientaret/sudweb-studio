<?php

declare(strict_types=1);

/**
 * Projets livrés — source unique, partagée par la page Réalisations
 * (frontend/static/realisations.php) et par la section « Nos derniers projets »
 * de l'accueil, qui en prend les premiers.
 *
 * L'ordre du tableau est l'ordre d'affichage : le plus récent en tête.
 *
 * Champs :
 *   cat    clé de catégorie, pour les filtres — voir 'categories' plus bas ;
 *   metier métier du client, affiché sur les cartes de l'accueil ;
 *   desc   descriptif de la page Réalisations (HTML simple autorisé : &nbsp;, <b>) ;
 *   tags   prestations, affichées en pastilles ;
 *   url    site en ligne — son domaine sert d'étiquette dans le cadre ;
 *   image  nom de base dans public/assets/img/ (sans extension, généré depuis
 *          frontend/src/img/work/ par `npm run build`).
 */

return [

    /*
     * Catégories de filtre : clé => libellé. L'ordre fixe celui des boutons.
     * Une catégorie sans projet n'est pas affichée.
     */
    'categories' => [
        'vitrine'   => 'Sites vitrines',
        'surmesure' => 'Web sur-mesure',
        'event'     => 'Événementiel',
    ],

    'projets' => [

        [
            'cat'    => 'vitrine',
            'title'  => 'Ariane Sécurité',
            'metier' => 'Alarme et vidéosurveillance',
            'desc'   => "Site vitrine pour un installateur d'alarme et de vidéosurveillance du "
                . "Lot-et-Garonne&nbsp;: deux parcours séparés pour les particuliers et les "
                . "professionnels, demande de devis mise en avant et référencement local sur Agen, "
                . "Villeneuve-sur-Lot et Marmande.",
            'tags'   => ['Web design', 'Développement', 'SEO local'],
            'url'    => 'https://ariane-securite.fr/',
            'image'  => 'ariane-securite',
        ],

        [
            'cat'    => 'surmesure',
            'title'  => 'AppleMousse Studio',
            'metier' => "Studio d'applications mobiles",
            'desc'   => "Un site vitrine dynamique pour un studio d'applications mobiles&nbsp;: univers "
                . "de marque affirmé, animations soignées et présentation claire du savoir-faire pour "
                . "convaincre de nouveaux clients.",
            'tags'   => ['Identité de marque', 'Web design', 'Développement'],
            'url'    => 'https://applemousse-studio.fr',
            'image'  => 'applemousse',
        ],

        [
            'cat'    => 'vitrine',
            'title'  => 'Cuisines Lamy',
            'metier' => "Cuisiniste près d'Artigues",
            'desc'   => "Vitrine élégante pour un cuisiniste près d'Artigues&nbsp;: mise en valeur des "
                . "réalisations, parcours de prise de contact simplifié et référencement local pour "
                . "générer des demandes de devis.",
            'tags'   => ['Web design', 'Développement', 'SEO local'],
            'url'    => 'https://www.cuisineslamy.com',
            'image'  => 'cuisineslamy',
        ],

        [
            'cat'    => 'event',
            'title'  => "Rad'o Lot",
            'metier' => 'Événement caritatif — course de radeaux',
            'desc'   => "Site événementiel pour une course de radeaux caritative sur le Lot&nbsp;: "
                . "programme, inscriptions et esprit convivial de la manifestation réunis dans une "
                . "identité joyeuse et lisible.",
            'tags'   => ['Identité de marque', 'Web design', 'Développement'],
            'url'    => 'https://radolot.fr',
            'image'  => 'radolot',
        ],

        [
            'cat'    => 'vitrine',
            'title'  => "Cabinet d'Artagnan",
            'metier' => "Cabinet d'affaires en fonds de commerce",
            'desc'   => "Présence professionnelle et rassurante pour un cabinet d'affaires spécialisé "
                . "dans la cession de fonds de commerce&nbsp;: ton sérieux, contenus structurés et "
                . "référencement pour asseoir la crédibilité.",
            'tags'   => ['Web design', 'Développement', 'Référencement'],
            'url'    => 'https://www.cabinet-dartagnan.com',
            'image'  => 'dartagnan',
        ],

    ],
];
