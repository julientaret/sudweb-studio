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
?>
    <footer class="site-footer">
        <div class="wrap">
            <p>&copy; <?= date('Y') ?> <?= e($copyright) ?></p>
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
