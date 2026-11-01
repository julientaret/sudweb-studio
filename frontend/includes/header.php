<?php
/**
 * Ouverture du document HTML commun au site public : <head> (titre, méta, CSS)
 * et barre d'en-tête. Partagé par les pages statiques (frontend/static) et par
 * le layout des pages dynamiques du CMS (front_layout).
 *
 * Les valeurs de gabarit (nom, langue, navigation, CTA) viennent de la config
 * centralisée frontend/config/site.php, via site(). La page ne fournit que ce
 * qui lui est propre :
 *
 * @var string $title        titre de la page (optionnel)
 * @var string $description  méta description de la page (optionnel)
 * @var string $activeNav    URL de nav à marquer active, ex. '/realisations' (optionnel)
 * @var bool   $bodyClass    classe supplémentaire sur <body> (optionnel)
 */

require_once __DIR__ . '/helpers.php';

$title       = $title ?? '';
$description = $description ?? (string) site('description', '');
$siteName    = site('name', 'Sud Web Studio');
$nav         = (array) site('nav', []);
$cta         = (array) site('cta', []);
$activeNav   = $activeNav ?? current_path();
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
    <meta name="theme-color" content="#121b27">

    <?php /* Marque <html> avant le rendu : sans cette classe, le CSS laisse les
             éléments animés visibles — pas de flash de contenu masqué si le JS
             tarde ou échoue. Fichier séparé plutôt qu'inline : la CSP du noyau
             est en script-src 'self' (pas de 'unsafe-inline'). */ ?>
    <script src="<?= e(asset_url('/assets/js-flag.js')) ?>"></script>

    <link rel="stylesheet" href="<?= e(asset_url('/assets/vendor/aos/aos.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/site.css')) ?>">
</head>
<body<?= isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
<?php require __DIR__ . '/brand.php'; ?>

<a class="skip-link" href="#contenu">Aller au contenu</a>

<header class="nav">
    <div class="nav-inner">
        <a class="brand" href="<?= e(site_url('/')) ?>" aria-label="<?= e($siteName) ?> — accueil">
            <span class="mk"><svg aria-hidden="true" focusable="false"><use href="#sws-mark"></use></svg></span>
            <span class="wm"><?= e($siteName) ?></span>
        </a>

        <?php if ($nav): ?>
        <nav class="nav-links" aria-label="Navigation principale">
            <?php foreach ($nav as $label => $url): ?>
            <a href="<?= e(site_url($url)) ?>"<?= nav_is_active($url, $activeNav) ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php if ($cta): ?>
        <a class="btn btn-primary btn-sm" href="<?= e(site_url((string) ($cta['url'] ?? '/'))) ?>" data-magnetic="0.25">
            <?= e((string) ($cta['label'] ?? 'Discutons de votre projet')) ?> <span class="ico" aria-hidden="true">↗</span>
        </a>
        <?php endif; ?>

        <button type="button" class="nav-burger" aria-expanded="false" aria-controls="nav-panel" aria-label="Ouvrir le menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<?php /* Menu plein écran mobile : mêmes entrées que la nav principale. */ ?>
<div class="nav-panel" id="nav-panel" aria-hidden="true">
    <?php foreach ($nav as $label => $url): ?>
    <a href="<?= e(site_url($url)) ?>"<?= nav_is_active($url, $activeNav) ? ' class="active"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if ($cta): ?>
    <a class="btn btn-primary" href="<?= e(site_url((string) ($cta['url'] ?? '/'))) ?>">
        <?= e((string) ($cta['label'] ?? 'Discutons de votre projet')) ?> <span class="ico" aria-hidden="true">↗</span>
    </a>
    <?php endif; ?>
</div>
