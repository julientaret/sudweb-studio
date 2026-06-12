<?php
/**
 * Ouverture du document HTML commun au site public : <head> (titre + CSS) et
 * barre d'en-tête. Partagé par les pages statiques (frontend/static) et par le
 * layout des pages dynamiques du CMS (front_layout).
 *
 * @var string $title     titre de la page (optionnel)
 * @var string $siteName  nom du site (optionnel, défaut depuis la config)
 */

use App\Core\Config;

$base = rtrim((string) Config::get('app.base_path', ''), '/');
$siteName = $siteName ?? (string) Config::get('app.site_name', 'Flow');
$title = $title ?? '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title !== '' ? e($title) . ' — ' : '' ?><?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/site.css">
</head>
<body>
    <header class="site-header">
        <div class="wrap">
            <a class="site-brand" href="<?= e($base) ?>/"><?= e($siteName) ?></a>
        </div>
    </header>
