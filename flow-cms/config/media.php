<?php

declare(strict_types=1);

/**
 * Configuration de la bibliothèque d'images (brief §5).
 */
return [
    // Déclinaisons générées à l'upload. Nommage dérivable (img-thumb.webp…),
    // aucune ligne DB par variante : on reconstruit le chemin depuis le filename.
    //   cover = remplit la boîte et recadre ; fit = garde le ratio sans rogner.
    'sizes' => [
        'thumb'  => ['w' => 200,  'h' => 200,  'mode' => 'cover'],
        'medium' => ['w' => 800,  'h' => null, 'mode' => 'fit'],
        'large'  => ['w' => 1600, 'h' => null, 'mode' => 'fit'],
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
