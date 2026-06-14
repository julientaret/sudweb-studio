<?php

declare(strict_types=1);

/**
 * Configuration de la bibliothèque d'images (brief §5).
 */
return [
    // Déclinaisons générées à l'upload. Nommage dérivable (img-mobile.webp…),
    // aucune ligne DB par variante : on reconstruit le chemin depuis le filename.
    //   cover = remplit la boîte et recadre ; fit = garde le ratio sans rogner.
    //
    // thumb = vignette carrée de la bibliothèque admin (hors front).
    // mobile/tablet/desktop = MÊME visuel décliné en 3 largeurs : le front les
    // sert toutes dans un seul srcset, le navigateur choisit selon l'écran.
    // En mode fit on n'agrandit jamais : la largeur réelle est bornée à la source
    // (ou au recadrage), d'où le calcul des descripteurs « w » dans ImageBlock.
    'sizes' => [
        'thumb'   => ['w' => 200,  'h' => 200,  'mode' => 'cover'],
        'mobile'  => ['w' => 640,  'h' => null, 'mode' => 'fit'],
        'tablet'  => ['w' => 1024, 'h' => null, 'mode' => 'fit'],
        'desktop' => ['w' => 1600, 'h' => null, 'mode' => 'fit'],
    ],

    // Largeurs front, dans l'ordre croissant, pour bâtir le srcset responsive.
    // (Sous-ensemble de « sizes » réellement servi au public.)
    'srcset_widths' => ['mobile', 'tablet', 'desktop'],

    // Attribut `sizes` par défaut des images de contenu. « 100vw » ne sous-charge
    // jamais (pas d'image floue) ; à affiner selon la largeur réelle du thème si
    // le contenu n'occupe pas toute la fenêtre. Modifiable sans rien régénérer.
    'sizes_attr' => '100vw',

    // Ratios proposés au recadrage (verrouillent le Cropper). null = libre.
    // Le ratio n'est PAS stocké : il est encodé par les dimensions du crop.
    'ratios' => [
        'libre'    => ['label' => 'Libre',         'value' => null],
        'banniere' => ['label' => 'Bannière 16:5', 'value' => 1600 / 500],
        'paysage'  => ['label' => 'Paysage 3:2',   'value' => 3 / 2],
        'carre'    => ['label' => 'Carré 1:1',     'value' => 1.0],
    ],

    // Types réels acceptés (vérifiés via finfo, jamais via l'extension/Content-Type).
    'allowed_mimes' => [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ],

    // Taille maximale d'un upload, en octets (5 Mo). Vérifiée en plus des limites PHP.
    'max_size' => 5 * 1024 * 1024,

    // Qualités d'encodage.
    'jpeg_quality' => 85,
    'webp_quality' => 82,
];
