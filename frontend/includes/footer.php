<?php
/**
 * Pied de page commun et fermeture du document HTML du site public.
 *
 * Valeurs de gabarit (navigation, copyright, liens sociaux) issues de la config
 * centralisée frontend/config/site.php, via site(). Les liens vers les pages
 * légales viennent, eux, du module LegalPages (publiées dans l'admin).
 */

require_once __DIR__ . '/helpers.php';

$siteName  = site('name', 'Sud Web Studio');
$copyright = (string) (site('footer', [])['copyright'] ?? $siteName);
$nav       = (array) site('nav', []);
$social    = (array) site('social', []);

// Pictogrammes des réseaux reconnus. Un libellé inconnu reste affiché en texte.
$socialIcons = [
    'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
    'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
    'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
    'dribbble'  => '<circle cx="12" cy="12" r="10"/><path d="M19.13 5.09C15.22 9.14 10 10.44 2.25 10.94M21.75 12.84c-6.62-1.41-12.14 1-16.38 6.32M8.56 2.75c4.37 6 6 9.42 8 17.72"/>',
    'behance'   => '<path d="M2 7h5.5a2.5 2.5 0 0 1 0 5H2zM2 12h6a2.5 2.5 0 0 1 0 5H2zM15 9h6M14.5 14h7a3.5 3.5 0 0 0-7 0 3.5 3.5 0 0 0 6 2.5"/>',
];

// Liens vers les pages légales publiées (module LegalPages). Dynamique : un lien
// par page publiée dans l'admin. Best-effort : si le module est désactivé ou la
// table absente, aucun lien (jamais d'erreur sur le site public).
$legalLinks = [];
if (in_array('LegalPages', (array) \App\Core\Config::get('modules.enabled', []), true)) {
    try {
        $legalLinks = (new \App\Modules\LegalPages\LegalPageRepository())->publishedLinks();
    } catch (\Throwable) {
        $legalLinks = [];
    }
}
?>
    <footer class="foot">
        <div class="shell">
            <div class="foot-bar">
                <a class="brand" href="<?= e(site_url('/')) ?>" aria-label="<?= e($siteName) ?> — accueil">
                    <span class="mk"><svg aria-hidden="true" focusable="false"><use href="#sws-mark"></use></svg></span>
                    <span class="wm"><?= e($siteName) ?></span>
                </a>

                <?php if ($nav): ?>
                <nav class="foot-nav" aria-label="Pied de page">
                    <?php foreach ($nav as $label => $url): ?>
                    <a href="<?= e(site_url($url)) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <?php if ($social): ?>
                <div class="foot-social">
                    <?php foreach ($social as $label => $url):
                        $icon = $socialIcons[strtolower((string) $label)] ?? null; ?>
                    <a href="<?= e($url) ?>" rel="noopener me" target="_blank" aria-label="<?= e($label) ?>">
                        <?php if ($icon !== null): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icon ?></svg>
                        <?php else: ?>
                        <?= e($label) ?>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="foot-base">
                <span>&copy; <span data-year><?= date('Y') ?></span> <?= e($copyright) ?> — Tous droits réservés</span>
                <?php if ($legalLinks): ?>
                <nav class="links" aria-label="Pages légales">
                    <?php foreach ($legalLinks as $link): ?>
                    <a href="<?= e(site_url('/' . $link['slug'])) ?>"><?= e($link['title']) ?></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <?php /* Mesure d'audience Simple Analytics — sans cookie ni donnée
             personnelle. Le domaine est autorisé dans la CSP du noyau via
             'csp_extra' (flow-cms/config/security.php), sans quoi le
             navigateur bloquerait le script sans rien dire. */ ?>
    <script async src="https://scripts.simpleanalyticscdn.com/latest.js"></script>

    <script src="<?= e(asset_url('/assets/vendor/aos/aos.js')) ?>" defer></script>
    <script src="<?= e(asset_url('/assets/site.js')) ?>" defer data-aos-duration="<?= e((string) ($aosDuration ?? 800)) ?>"></script>
</body>
</html>
