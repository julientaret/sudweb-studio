<?php

declare(strict_types=1);

/**
 * Catalogue des modules installés pour ce site (brief §3 : activation par site
 * en une ligne). L'ordre détermine l'ordre de boot (routes, menu, migrations).
 *
 * Convention : "Users" → App\Modules\Users\UsersModule.
 *
 * L'admin peut ensuite désactiver certains modules du catalogue depuis l'onglet
 * Administration (/admin/administration) ; l'état vit dans storage/modules.json
 * et la liste effective est publiée au boot dans Config 'modules.enabled'
 * (voir App\Core\ModuleSettings).
 */
return [
    'available' => [
        'Users',
        'Media',
        'Documents',
        'Pages',
        'Agenda',
        'Actualites',
        'Realisations',
        'Sliders',
        'GoogleReviews',
        'Horaires',
        'Contact',
        'Devis',
        'LegalPages',
        'Stats',
    ],
];
