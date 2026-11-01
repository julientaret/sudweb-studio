<?php
/**
 * Gabarit d'une page statique du site public.
 *
 *   cp frontend/static/_template.php frontend/static/mapage.php   → servie sur /mapage
 *
 * Le préfixe « _ » rend ce fichier non servable (cf. frontend/public/index.php).
 * $title et $description alimentent <title> et la méta description ; le reste du
 * squelette (en-tête, pied de page, CSS, JS) vient des partials.
 */

$title = 'Titre de la page';
$description = 'Phrase de description pour les moteurs de recherche (160 caractères max).';

require dirname(__DIR__) . '/includes/header.php';
?>

<main class="page shell" id="contenu">
    <div class="page-body">
        <h1><?= e($title) ?></h1>
        <p>Contenu de la page.</p>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php';
