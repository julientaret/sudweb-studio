<?php

declare(strict_types=1);

/**
 * Configuration de la bibliothèque de documents.
 *
 * Pendant du module Media, mais pour des fichiers bureautiques réutilisables sur
 * le site (PDF, Word, Excel, PowerPoint, OpenDocument, texte…). Aucun traitement
 * d'image : le fichier est stocké tel quel, sous un nom assaini.
 *
 * Sécurité : on ne fait jamais confiance à l'extension ni au Content-Type envoyés
 * par le navigateur. Le type réel est vérifié via finfo ; ET l'extension doit
 * figurer dans la liste blanche. L'exécution de scripts est par ailleurs coupée
 * sur public/documents/ (.htaccess).
 */
return [
    // Extensions autorisées → libellé lisible (affiché sur la vignette).
    // L'extension est le premier garde-fou : un .php ne passe jamais.
    'allowed' => [
        'pdf'  => 'PDF',
        'doc'  => 'Word',
        'docx' => 'Word',
        'xls'  => 'Excel',
        'xlsx' => 'Excel',
        'ppt'  => 'PowerPoint',
        'pptx' => 'PowerPoint',
        'odt'  => 'OpenDocument',
        'ods'  => 'OpenDocument',
        'odp'  => 'OpenDocument',
        'rtf'  => 'RTF',
        'txt'  => 'Texte',
        'csv'  => 'CSV',
    ],

    // Types réels acceptés (finfo). Second garde-fou, combiné à l'extension.
    // Les formats OOXML (docx/xlsx/pptx) et ODF sont des conteneurs ZIP : selon le
    // système, finfo renvoie le mime précis OU application/zip. Les anciens formats
    // Office (doc/xls/ppt) sont des « Compound File Binary » (x-cfb / vnd.ms-office).
    'allowed_mimes' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'application/zip',       // conteneur OOXML / ODF selon le système
        'application/x-cfb',     // doc / xls / ppt anciens (Compound File Binary)
        'application/vnd.ms-office',
        'application/octet-stream', // certains systèmes renvoient ce mime générique
        'application/rtf',
        'text/rtf',
        'text/plain',
        'text/csv',
    ],

    // Taille maximale d'un upload, en octets (20 Mo). Vérifiée en plus des limites PHP.
    'max_size' => 20 * 1024 * 1024,
];
