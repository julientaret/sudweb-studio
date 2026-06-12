<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Emplacement physique et URL publique des fichiers uploadés (images & documents).
 *
 * Par défaut (valeurs vides), tout vit sous public/ : public/uploads et
 * public/documents, servis aux URLs {base_path}/uploads et {base_path}/documents.
 * En dev, laissez tel quel.
 *
 * En PRODUCTION, on peut déplacer le STOCKAGE hors du dossier flow-cms via
 * .env.local — pour que git/rsync et les déploiements ne touchent jamais aux
 * fichiers des utilisateurs :
 *
 *   UPLOADS_PATH=/var/data/flow/uploads          chemin disque absolu
 *   DOCUMENTS_PATH=/var/data/flow/documents
 *
 * Deux cas pour l'URL :
 *  - Le dossier externe reste servi à la même URL (symlink depuis public/, ou
 *    Alias Apache / location Nginx) : laissez *_URL vide, l'URL demeure
 *    {base_path}/uploads — aucune autre config.
 *  - Le dossier est servi ailleurs (sous-domaine, CDN…) : fixez l'URL complète.
 *
 *   UPLOADS_URL=https://cdn.exemple.fr/uploads
 *   DOCUMENTS_URL=https://cdn.exemple.fr/documents
 *
 * IMPORTANT (sécurité) : la coupure d'exécution de scripts vit dans les .htaccess
 * de public/uploads et public/documents. Si vous déplacez le stockage, recopiez
 * ces .htaccess (ou la règle équivalente du vhost) dans le nouveau dossier.
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
