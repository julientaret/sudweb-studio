<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Emplacement physique et URL publique des fichiers uploadés (images & documents).
 *
 * Par défaut (valeurs vides), uploads (médias) ET documents vivent dans le site
 * public, sous frontend/public/uploads et frontend/public/documents — la MÊME URL
 * depuis l'admin et depuis le front, pour qu'un média ou un lien de page ne casse
 * jamais (voir MediaStorage et DocumentStorage).
 *
 * L'URL est résolue en trois paliers : *_URL explicite ci-dessous (CDN…), sinon
 * APP_URL + /uploads (URL absolue du site, recommandé — indépendant du nom du
 * dossier d'installation), sinon le chemin auto-détecté {public_base_path}/uploads.
 * En dev, laissez tel quel (renseignez APP_URL dans .env.local).
 *
 * En PRODUCTION, on peut déplacer le STOCKAGE hors du dossier flow-cms via
 * .env.local — pour que git/rsync et les déploiements ne touchent jamais aux
 * fichiers des utilisateurs :
 *
 *   UPLOADS_PATH=/var/data/flow/uploads          chemin disque absolu
 *   DOCUMENTS_PATH=/var/data/flow/documents
 *
 * Deux cas pour l'URL :
 *  - Le dossier externe reste servi à la même URL (symlink depuis frontend/public,
 *    ou Alias Apache / location Nginx) : laissez *_URL vide, l'URL est dérivée de
 *    APP_URL (ou auto-détectée) — aucune autre config.
 *  - Le dossier est servi ailleurs (sous-domaine, CDN…) : fixez l'URL complète.
 *
 *   UPLOADS_URL=https://cdn.exemple.fr/uploads
 *   DOCUMENTS_URL=https://cdn.exemple.fr/documents
 *
 * IMPORTANT (sécurité) : la coupure d'exécution de scripts vit dans les .htaccess
 * de frontend/public/uploads et frontend/public/documents. Si vous déplacez le
 * stockage, recopiez ces .htaccess (ou la règle équivalente du vhost) dans le
 * nouveau dossier.
 */
return [
    'uploads' => [
        'path' => (string) Config::env('UPLOADS_PATH', ''),
        'url'  => (string) Config::env('UPLOADS_URL', ''),
    ],
    'documents' => [
        'path' => (string) Config::env('DOCUMENTS_PATH', ''),
        'url'  => (string) Config::env('DOCUMENTS_URL', ''),
    ],
];
