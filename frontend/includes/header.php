<?php
/**
 * Ouverture du document HTML commun au site public : <head> (titre, méta, CSS)
 * et barre d'en-tête. Partagé par les pages statiques (frontend/static) et par
 * le layout des pages dynamiques du CMS (front_layout).
 *
 * Les valeurs de gabarit (nom, langue, navigation) viennent de la config
 * centralisée frontend/config/site.php, via site(). La page ne fournit que ce
 * qui lui est propre :
 *
 * @var string $title        titre de la page (optionnel)
 * @var string $description  méta description de la page (optionnel)
 */

require_once __DIR__ . '/helpers.php';

$title = $title ?? '';
$description = $description ?? (string) site('description', '');
$siteName = site('name', 'Flow');
?>
<!doctype html>
<html lang="<?= e(site('lang', 'fr')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title !== '' ? e($title) . ' — ' : '' ?><?= e($siteName) ?></title>
    <?php if ($description !== ''): ?>
    <meta name="description" content="<?= e($description) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(site_url('/assets/site.css')) ?>">
</head>
<body>
    <header class="site-header">
        <div class="wrap">
            <a class="site-brand" href="<?= e(site_url('/')) ?>"><?= e($siteName) ?></a>
            <?php if ($nav = site('nav', [])): ?>
            <nav class="site-nav">
                <?php foreach ($nav as $label => $url): ?>
                <a href="<?= e(site_url($url)) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <?php if (module_enabled('Devis')): ?>
                <a href="#" class="devis-trigger" data-devis-open>Demander un devis</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </header>
