<?php

declare(strict_types=1);

/**
 * GABARIT DE PAGE STATIQUE — copie-moi pour créer une nouvelle page.
 *
 *   cp frontend/static/_template.php frontend/static/contact.php
 *   → la page est servie sur /contact (slug = nom de fichier).
 *
 * Ce fichier n'est pas servi tel quel : le préfixe « _ » le rend non routable.
 *
 * Renseigne $title et (optionnel) $description, écris ton contenu entre les
 * partials header/footer — qui apportent <head>, CSS, en-tête et pied de page
 * communs (paramétrés dans frontend/config/site.php).
 */

$title = '';          // ex. 'Contact'
$description = '';     // ex. 'Contactez notre équipe.' (méta description)

require dirname(__DIR__) . '/includes/header.php';
?>

<main class="wrap">

    <h1>Titre de la page</h1>
    <p>Contact</p>

</main>

<?php
require dirname(__DIR__) . '/includes/footer.php';
