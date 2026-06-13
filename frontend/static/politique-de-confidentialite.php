<?php

declare(strict_types=1);

/**
 * Politique de confidentialité (page statique) — référencée par le formulaire de
 * contact (config/contact.php → privacy_url). Texte de base à adapter au client
 * (responsable de traitement, durée exacte, coordonnées…).
 */

use App\Core\Config;

$siteName = (string) (function_exists('site') ? site('name', 'le site') : 'le site');
$retention = (int) Config::get('contact.retention_months', 12);

$title = 'Politique de confidentialité';
$description = 'Comment vos données personnelles sont traitées sur ce site.';

require dirname(__DIR__) . '/includes/header.php';
?>

<main class="wrap legal">

    <h1>Politique de confidentialité</h1>

    <p>La présente politique explique comment <?= e($siteName) ?> traite les données
       personnelles transmises via le formulaire de contact.</p>

    <h2>Données collectées</h2>
    <p>Lorsque vous utilisez le formulaire de contact, nous collectons :</p>
    <ul>
        <li>votre <strong>nom</strong> ;</li>
        <li>votre <strong>adresse e-mail</strong> ;</li>
        <li>le <strong>sujet</strong> et le <strong>message</strong> de votre demande.</li>
    </ul>
    <p>Pour prévenir les abus (envois automatisés), une <strong>empreinte technique
       de votre adresse IP</strong> (valeur hachée, non réversible) est conservée
       temporairement. Votre adresse IP n’est jamais stockée en clair.</p>

    <h2>Finalité et base légale</h2>
    <p>Ces données sont utilisées <strong>uniquement pour traiter votre demande</strong>
       et y répondre. Le traitement repose sur votre <strong>consentement</strong>,
       recueilli au moment de l’envoi du formulaire.</p>

    <h2>Destinataires</h2>
    <p>Vos données sont adressées au responsable du site et ne sont
       <strong>jamais cédées ni revendues à des tiers</strong>.</p>

    <h2>Durée de conservation</h2>
    <p>Les demandes sont conservées au maximum <strong><?= e($retention) ?> mois</strong>,
       puis supprimées automatiquement.</p>

    <h2>Vos droits</h2>
    <p>Conformément au RGPD, vous disposez d’un droit d’accès, de rectification,
       d’effacement, de limitation et d’opposition concernant vos données. Pour
       exercer ces droits, contactez-nous via le <a href="<?= e(site_url('/contact')) ?>">formulaire de contact</a>.
       Vous pouvez également introduire une réclamation auprès de la CNIL.</p>

</main>

<style>
    .legal { max-width: 720px; }
    .legal h2 { margin-top: 1.8rem; }
    .legal ul { padding-left: 1.3rem; line-height: 1.7; }
    .legal p { line-height: 1.7; }
</style>

<?php
require dirname(__DIR__) . '/includes/footer.php';
