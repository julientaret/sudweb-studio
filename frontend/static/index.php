<?php

declare(strict_types=1);

/**
 * Page d'accueil du site public (statique, écrite à la main).
 *
 * Servie à la racine du site (ex. /flow/) par le front controller, qui résout
 * frontend/static/index.php avant toute page dynamique du CMS.
 *
 * Pour créer une nouvelle page, copier frontend/static/_template.php.
 */

$title = 'Accueil';
$description = '';

require dirname(__DIR__) . '/includes/header.php';
?>

<main class="wrap">
    <h1>Bienvenue sur Flow</h1>
    <p>Cette page d'accueil est une page statique (<code>frontend/static/index.php</code>),
       indépendante de l'administration.</p>
    <p>Pour ajouter une page : créez <code>frontend/static/&lt;slug&gt;.php</code>.
       Les pages gérées dans l'admin restent accessibles par leur slug.</p>
</main>

<?php
require dirname(__DIR__) . '/includes/footer.php';
