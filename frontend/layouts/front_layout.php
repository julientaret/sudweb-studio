<?php
/**
 * Thème des pages dynamiques du CMS (frontend) — surcharge le front_layout par
 * défaut des modules (cherché en premier via app.front_views).
 *
 * Réutilise les mêmes partials que les pages statiques : une seule définition
 * du squelette HTML (header/footer) pour tout le site public.
 *
 * @var string $title
 * @var string $body      HTML déjà rendu et échappé par les blocs
 * @var string $siteName
 */

require dirname(__DIR__) . '/includes/header.php';
?>
    <main class="wrap">
        <?= $body ?>
    </main>
<?php
require dirname(__DIR__) . '/includes/footer.php';
