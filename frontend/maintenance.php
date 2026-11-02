<?php

declare(strict_types=1);

/**
 * Page de maintenance du site public (HTTP 503).
 *
 * Surcharge la page intégrée du noyau : App\Core\MaintenanceSettings::renderPage()
 * inclut ce fichier s'il existe, après avoir posé le code 503 et les en-têtes
 * Retry-After / Cache-Control. On ne s'occupe donc ici que du HTML.
 *
 * Intégration de design/Sud Web Studio - Bientot de retour.html.
 *
 * Volontairement autonome : ni en-tête, ni navigation, ni pied de page du site
 * — pendant une maintenance, aucun de ces liens ne mène nulle part. Seule la
 * feuille de styles est partagée ; les fichiers de public/assets sont servis
 * directement par le serveur web, donc toujours joignables.
 */

require_once __DIR__ . '/includes/helpers.php';

$siteName = (string) site('name', 'Sud Web Studio');
$contact  = (array) site('contact', []);
$email    = (string) ($contact['email'] ?? '');
$region   = (string) ($contact['region'] ?? '');
?>
<!doctype html>
<html lang="<?= e(site('lang', 'fr')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bientôt de retour — <?= e($siteName) ?></title>
    <meta name="description" content="<?= e($siteName) ?> fait peau neuve. Le site revient très vite.">
    <meta name="theme-color" content="#121b27">
    <?php /* Une page de maintenance n'a rien à faire dans un index de recherche. */ ?>
    <meta name="robots" content="noindex">
    <?php /* Mêmes icônes de favori que le site (cf. includes/header.php) : la
             page est autonome, mais l'onglet doit rester celui du site. */ ?>
    <link rel="icon" href="<?= e(asset_url('/favicon.ico')) ?>" sizes="32x32">
    <link rel="icon" href="<?= e(asset_url('/assets/brand/icon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(asset_url('/assets/brand/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/site.css')) ?>">
</head>
<body class="maint">

    <main class="maint-main">
        <div class="maint-stage" aria-hidden="true">
            <span class="glow"></span>
            <img src="<?= e(asset_url('/assets/img/logo3d-hero.webp')) ?>" alt=""
                 width="1254" height="1254">
        </div>

        <span class="eyebrow">Bientôt de retour</span>
        <h1>Le studio se <em>refait une beauté</em></h1>
        <p class="lead">Notre site fait peau neuve. On revient très vite avec quelque chose de
            soigné. En attendant, vos projets, eux, n'attendent pas&nbsp;: écrivez-nous.</p>

        <?php if ($email !== ''): ?>
        <a class="maint-mail" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        <?php endif; ?>
    </main>

    <footer class="maint-foot">
        &copy;&nbsp;<?= date('Y') ?>&nbsp;<?= e($siteName) ?><?= $region !== '' ? ' — ' . e($region) : '' ?>
    </footer>

</body>
</html>
