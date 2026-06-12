<?php
/**
 * Pied de page commun et fermeture du document HTML du site public.
 *
 * @var string $siteName  nom du site (optionnel, défaut depuis la config)
 */

use App\Core\Config;

$siteName = $siteName ?? (string) Config::get('app.site_name', 'Flow');
?>
    <footer class="site-footer">
        <div class="wrap">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>
</body>
</html>
