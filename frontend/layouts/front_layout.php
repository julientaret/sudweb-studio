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

/*
 * Les modules n'alimentent pas le titre de la même façon : les Pages le posent
 * dans le corps (bloc Titre de l'éditeur), les Pages légales le passent à part.
 * On n'ajoute donc le <h1> que si le corps n'en contient pas déjà un — sans
 * quoi les pages du page-builder afficheraient leur titre en double.
 */
$hasHeading = (bool) preg_match('/<h1[\s>]/i', $body);
?>
    <main class="page shell" id="contenu">
        <div class="page-body rte">
            <?php if (!$hasHeading && $title !== ''): ?>
            <h1><?= e($title) ?></h1>
            <?php endif; ?>
            <?= $body ?>
        </div>
    </main>
<?php
require dirname(__DIR__) . '/includes/footer.php';
