<?php
/**
 * Pied de page commun et fermeture du document HTML du site public.
 *
 * Valeurs de gabarit (copyright, liens sociaux) issues de la config centralisée
 * frontend/config/site.php, via site().
 */

require_once __DIR__ . '/helpers.php';

$copyright = (string) (site('footer', [])['copyright'] ?? site('name', 'Flow'));
$social = site('social', []);

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
    <footer class="site-footer">
        <div class="wrap">
            <p>&copy; <?= date('Y') ?> <?= e($copyright) ?></p>
            <?php if ($legalLinks): ?>
            <nav class="site-legal" aria-label="Pages légales">
                <?php foreach ($legalLinks as $link): ?>
                <a href="<?= e(site_url('/' . $link['slug'])) ?>"><?= e($link['title']) ?></a>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
            <?php if ($social): ?>
            <p class="site-social">
                <?php foreach ($social as $label => $url): ?>
                <a href="<?= e($url) ?>" rel="noopener"><?= e($label) ?></a>
                <?php endforeach; ?>
            </p>
            <?php endif; ?>
        </div>
    </footer>
</body>
</html>
