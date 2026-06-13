<?php

declare(strict_types=1);

/**
 * Mapping rôle → permissions (brief §7).
 *
 * Le code teste toujours une permission (`module.action`), jamais un rôle.
 * Wildcards supportés : '*' (tout) et 'module.*' (toutes les actions d'un module).
 *
 * Pas de tables roles/permissions en v1 : le champ `role` sur users + ce mapping
 * suffisent. Le jour où un client veut des rôles sur mesure, on bascule ce mapping
 * vers la DB sans toucher une seule vérification dans le code.
 */
return [
    // Sud Web Studio : accès total. Couvre notamment l'espace `admin.*`
    // (ex. admin.modules : onglet Administration, choix des modules actifs),
    // qu'aucun autre rôle ne détient.
    'admin' => ['*'],

    // Client référent : gère le contenu, les médias, les documents et les
    // utilisateurs. Pour les avis Google, il consulte, synchronise (1×/jour) et
    // cure les avis, mais ne CHOISIT PAS l'établissement (googlereviews.configure,
    // réservé à l'agence via le wildcard admin). D'où la liste explicite plutôt
    // qu'un googlereviews.* qui couvrirait configure.
    'manager' => [
        'content.*', 'media.*', 'documents.*', 'users.manage',
        'googlereviews.view', 'googlereviews.sync', 'googlereviews.manage',
        'contacts.view', 'contacts.delete',
    ],

    // Rédacteur : édite du contenu, téléverse médias et documents, ne publie pas.
    'editor' => ['content.edit', 'media.upload', 'documents.upload'],
];
