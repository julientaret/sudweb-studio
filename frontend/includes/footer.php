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

    <?php if (module_enabled('Devis')):
        $devisTypes     = (array) \App\Core\Config::get('devis.project_types', []);
        $devisBudgets   = (array) \App\Core\Config::get('devis.budgets', []);
        $devisDeadlines = (array) \App\Core\Config::get('devis.deadlines', []);
        $devisConsent   = (string) \App\Core\Config::get('devis.consent_text', '');
        $devisPrivacy   = (string) \App\Core\Config::get('devis.privacy_url', '');
    ?>
    <button type="button" class="devis-trigger devis-fab" data-devis-open aria-haspopup="dialog" aria-controls="devis-modal">
        Demander un devis
    </button>

    <div class="devis-modal" id="devis-modal" aria-hidden="true"
         data-token-url="<?= e(site_url('/devis/token')) ?>"
         data-submit-url="<?= e(site_url('/devis/envoyer')) ?>">
        <div class="devis-overlay" data-devis-close></div>
        <div class="devis-dialog" role="dialog" aria-modal="true" aria-labelledby="devis-title">
            <button type="button" class="devis-x" data-devis-close aria-label="Fermer">&times;</button>
            <h2 id="devis-title">Demander un devis</h2>

            <div class="devis-steps">
                <span class="devis-dot is-active"></span>
                <span class="devis-dot"></span>
                <span class="devis-dot"></span>
            </div>

            <form class="devis-form" novalidate>
                <div class="devis-hp" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                    <input type="text" name="company_url" tabindex="-1" autocomplete="off">
                </div>

                <div class="devis-error" role="alert" hidden></div>

                <!-- Étape 1 : type de projet -->
                <fieldset class="devis-step is-active" data-step="1">
                    <legend>Votre projet</legend>
                    <div class="devis-field">
                        <label for="devis-type">Type de prestation</label>
                        <select id="devis-type" name="project_type" required>
                            <option value="" disabled selected>Choisissez…</option>
                            <?php foreach ($devisTypes as $t): ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </fieldset>

                <!-- Étape 2 : détails -->
                <fieldset class="devis-step" data-step="2">
                    <legend>Détails du besoin</legend>
                    <div class="devis-field">
                        <label for="devis-budget">Budget estimé</label>
                        <select id="devis-budget" name="budget" required>
                            <option value="" disabled selected>Choisissez…</option>
                            <?php foreach ($devisBudgets as $b): ?>
                            <option value="<?= e($b) ?>"><?= e($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="devis-field">
                        <label for="devis-deadline">Délai souhaité</label>
                        <select id="devis-deadline" name="deadline" required>
                            <option value="" disabled selected>Choisissez…</option>
                            <?php foreach ($devisDeadlines as $d): ?>
                            <option value="<?= e($d) ?>"><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="devis-field">
                        <label for="devis-desc">Décrivez votre projet</label>
                        <textarea id="devis-desc" name="description" rows="4" maxlength="5000" required></textarea>
                    </div>
                </fieldset>

                <!-- Étape 3 : coordonnées -->
                <fieldset class="devis-step" data-step="3">
                    <legend>Vos coordonnées</legend>
                    <div class="devis-field">
                        <label for="devis-name">Nom</label>
                        <input type="text" id="devis-name" name="name" maxlength="150" required autocomplete="name">
                    </div>
                    <div class="devis-field">
                        <label for="devis-company">Société <span class="opt">(facultatif)</span></label>
                        <input type="text" id="devis-company" name="company" maxlength="150" autocomplete="organization">
                    </div>
                    <div class="devis-field">
                        <label for="devis-email">E-mail</label>
                        <input type="email" id="devis-email" name="email" maxlength="255" required autocomplete="email">
                    </div>
                    <div class="devis-field">
                        <label for="devis-phone">Téléphone <span class="opt">(facultatif)</span></label>
                        <input type="tel" id="devis-phone" name="phone" maxlength="40" autocomplete="tel">
                    </div>
                    <div class="devis-consent">
                        <input type="checkbox" id="devis-consent" name="consent" value="1" required>
                        <label for="devis-consent">
                            <?= e($devisConsent !== '' ? $devisConsent : "J'accepte que mes données soient utilisées pour traiter ma demande.") ?>
                            <?php if ($devisPrivacy !== ''): ?>
                            <a href="<?= e(site_url($devisPrivacy)) ?>" target="_blank" rel="noopener">En savoir plus</a>.
                            <?php endif; ?>
                        </label>
                    </div>
                </fieldset>

                <div class="devis-actions">
                    <button type="button" class="devis-btn devis-btn-ghost devis-prev" hidden>Précédent</button>
                    <span class="spacer"></span>
                    <button type="button" class="devis-btn devis-btn-primary devis-next">Suivant</button>
                    <button type="submit" class="devis-btn devis-btn-primary devis-submit" hidden>Envoyer la demande</button>
                </div>

                <p class="devis-note">Vos données servent uniquement à traiter votre demande et ne sont jamais cédées à des tiers.</p>
            </form>

            <div class="devis-success" hidden>
                <div class="check" aria-hidden="true">✓</div>
                <p>Merci, votre demande a bien été envoyée.<br>Nous revenons vers vous très rapidement.</p>
                <button type="button" class="devis-btn devis-btn-ghost" data-devis-close>Fermer</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="<?= e(site_url('/assets/site.js')) ?>" defer></script>
</body>
</html>
