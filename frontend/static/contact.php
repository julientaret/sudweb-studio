<?php

declare(strict_types=1);

/**
 * Page de contact (statique) — formulaire public du module Contact.
 *
 * Le site public est sans état ; on démarre ici une session LÉGÈRE, propre au
 * formulaire, uniquement pour porter le jeton CSRF, l'horodatage anti-bot et les
 * messages flash (erreurs + ressaisie). Le traitement de l'envoi est fait côté
 * noyau par App\Modules\Contact\ContactController::submit (route POST
 * /contact/envoyer). Voir flow-cms/docs/modules/contact.md.
 *
 * Anti-spam : honeypot (champs pièges invisibles) + délai minimum + rate-limit
 * par IP côté serveur. Aucun service tiers, aucun cookie de pistage → RGPD-clean.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Modules\Contact\ContactModule;

// Session légère du formulaire (cookie strictement nécessaire : sécurité CSRF),
// avec cookies durcis (HttpOnly, SameSite, Secure si HTTPS).
ContactModule::startSession();

// Flash (erreurs + ressaisie) déposé par le contrôleur en cas d'échec.
$flash  = $_SESSION['contact_flash'] ?? ['errors' => [], 'old' => []];
unset($_SESSION['contact_flash']);
$errors = (array) ($flash['errors'] ?? []);
$old    = (array) ($flash['old'] ?? []);
$sent   = isset($_GET['envoye']);

// Jeton anti-bot : horodatage d'affichage du formulaire.
$_SESSION['contact_form_ts'] = time();

$subjects    = (array) Config::get('contact.subjects', []);
$consentText = (string) Config::get('contact.consent_text', '');
$privacyUrl  = (string) Config::get('contact.privacy_url', '');

// Action du formulaire : URL ABSOLUE dérivée d'APP_URL (source de vérité de la
// portabilité du projet, README §3.3). On ne s'appuie pas sur le base_path
// auto-détecté seul : si APP_URL est renseignée, elle prime ; sinon repli sur
// le chemin relatif au base_path (site_url).
$appUrl     = rtrim((string) Config::get('app.url', ''), '/');
$formAction = $appUrl !== '' ? $appUrl . '/contact/envoyer' : site_url('/contact/envoyer');

$title = 'Contact';
$description = 'Contactez Sud Web Studio, studio de création de sites web à '
    . 'Villeneuve-sur-Lot (47) : parlons de votre projet.';

require dirname(__DIR__) . '/includes/header.php';
?>

<main class="page shell" id="contenu">
<div class="page-body" id="formulaire">

    <h1>Contact</h1>
    <p>Une question, une demande ? Écrivez-nous, nous vous répondrons rapidement.</p>

    <?php if ($sent): ?>
        <div class="form-alert form-alert--ok" role="status">
            Merci, votre message a bien été envoyé. Nous vous répondrons dès que possible.
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert form-alert--error" role="alert">
            <p>Votre message n’a pas pu être envoyé :</p>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="contact-form" method="post" action="<?= e($formAction) ?>" novalidate>
        <?= Csrf::field() ?>

        <label for="cf-name">Nom <span aria-hidden="true">*</span></label>
        <input type="text" id="cf-name" name="name" maxlength="150" required
               value="<?= e($old['name'] ?? '') ?>" autocomplete="name">

        <label for="cf-email">E-mail <span aria-hidden="true">*</span></label>
        <input type="email" id="cf-email" name="email" maxlength="255" required
               value="<?= e($old['email'] ?? '') ?>" autocomplete="email">

        <label for="cf-subject">Sujet</label>
        <?php if ($subjects !== []): ?>
            <select id="cf-subject" name="subject">
                <?php foreach ($subjects as $s): ?>
                    <option value="<?= e($s) ?>" <?= (($old['subject'] ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="text" id="cf-subject" name="subject" maxlength="200" value="<?= e($old['subject'] ?? '') ?>">
        <?php endif; ?>

        <label for="cf-message">Message <span aria-hidden="true">*</span></label>
        <textarea id="cf-message" name="message" rows="7" maxlength="5000" required><?= e($old['message'] ?? '') ?></textarea>

        <?php /* Honeypot : invisible pour les humains, rempli par les bots. */ ?>
        <div class="cf-hp" aria-hidden="true">
            <label for="cf-website">Ne remplissez pas ce champ</label>
            <input type="text" id="cf-website" name="website" tabindex="-1" autocomplete="off">
            <input type="text" name="company_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="cf-consent">
            <input type="checkbox" id="cf-consent" name="consent" value="1" required>
            <label for="cf-consent">
                <?= e($consentText !== '' ? $consentText : "J'accepte que mes données soient utilisées pour traiter ma demande.") ?>
                <span aria-hidden="true">*</span>
            </label>
        </div>

        <button type="submit" class="btn-submit">Envoyer le message</button>

        <p class="cf-note">
            Les champs marqués d’un <span aria-hidden="true">*</span> sont obligatoires.
            Vos données servent uniquement à traiter votre demande et ne sont jamais cédées à des tiers.
            <?php if ($privacyUrl !== ''): ?>
                En savoir plus : <a href="<?= e(site_url($privacyUrl)) ?>">politique de confidentialité</a>.
            <?php endif; ?>
        </p>
    </form>

</div>
</main>

<?php
// Styles dans frontend/src/css/contact.css (compilé dans public/assets/site.css).
require dirname(__DIR__) . '/includes/footer.php';
