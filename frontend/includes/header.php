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
 * @var string $ogImage      image d'aperçu propre à la page (optionnel)
 * @var string $ogType       type Open Graph, 'article' pour un contenu daté (optionnel)
 */

require_once __DIR__ . '/helpers.php';

$title       = $title ?? '';
$description = $description ?? (string) site('description', '');
$siteName    = site('name', 'Sud Web Studio');
$nav         = (array) site('nav', []);
$cta         = (array) site('cta', []);
$activeNav   = $activeNav ?? current_path();

// Titre complet du document : partagé par <title> et og:title, pour que l'onglet
// et l'aperçu d'un lien portent toujours le même intitulé.
$metaTitle = ($title !== '' ? $title . ' — ' : '') . $siteName;

$og      = (array) site('og', []);
$ogType  = $ogType ?? 'website';
$ogImage = $ogImage ?? (string) ($og['image'] ?? '');
?>
<!doctype html>
<html lang="<?= e(site('lang', 'fr')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($metaTitle) ?></title>
    <?php if ($description !== ''): ?>
    <meta name="description" content="<?= e($description) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#121b27">

    <?php /* Icônes de favori, produites par `npm run build:brand` depuis
             frontend/src/brand/icon.svg. Trois suffisent : le SVG pour les
             navigateurs modernes (net à toute taille), l'ICO pour les autres —
             il répond aussi aux requêtes /favicon.ico spontanées — et le PNG
             180 px pour l'écran d'accueil iOS, qui n'accepte pas le SVG. */ ?>
    <link rel="icon" href="<?= e(asset_url('/favicon.ico')) ?>" sizes="32x32">
    <link rel="icon" href="<?= e(asset_url('/assets/brand/icon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(asset_url('/assets/brand/apple-touch-icon.png')) ?>">

    <?php /* Aperçu des partages (Open Graph, lu aussi par X, LinkedIn, Slack,
             WhatsApp…). URLs absolues obligatoires : les robots ne résolvent pas
             les chemins relatifs. Réglages par défaut dans
             frontend/config/site.php, clé 'og'. */ ?>
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:locale" content="<?= e((string) ($og['locale'] ?? 'fr_FR')) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <?php if ($description !== ''): ?>
    <meta property="og:description" content="<?= e($description) ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= e(abs_url(current_path())) ?>">
    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= e(asset_abs_url($ogImage)) ?>">
    <meta property="og:image:width" content="<?= e((string) ($og['width'] ?? 1200)) ?>">
    <meta property="og:image:height" content="<?= e((string) ($og['height'] ?? 630)) ?>">
    <?php if (($og['alt'] ?? '') !== ''): ?>
    <meta property="og:image:alt" content="<?= e((string) $og['alt']) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>

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
