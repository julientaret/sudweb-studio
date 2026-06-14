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
        $devisSteps   = (new \App\Modules\Devis\DevisFormSettings())->schema();
        $devisConsent = (string) \App\Core\Config::get('devis.consent_text', '');
        $devisPrivacy = (string) \App\Core\Config::get('devis.privacy_url', '');
        $devisLast    = count($devisSteps) - 1;
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
                <?php foreach ($devisSteps as $i => $devisStep): ?>
                <span class="devis-dot<?= $i === 0 ? ' is-active' : '' ?>"></span>
                <?php endforeach; ?>
            </div>

            <form class="devis-form" novalidate>
                <div class="devis-hp" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                    <input type="text" name="company_url" tabindex="-1" autocomplete="off">
                </div>

                <div class="devis-error" role="alert" hidden></div>

                <?php foreach ($devisSteps as $i => $devisStep): ?>
                <fieldset class="devis-step<?= $i === 0 ? ' is-active' : '' ?>" data-step="<?= $i + 1 ?>">
                    <?php if ($devisStep['title'] !== ''): ?><legend><?= e($devisStep['title']) ?></legend><?php endif; ?>
                    <?php foreach ($devisStep['fields'] as $field):
                        $fid  = 'devis-f-' . e($field['key']);
                        $req  = $field['required'] ? ' required' : '';
                        $optTag = $field['required'] ? '' : ' <span class="opt">(facultatif)</span>';
                    ?>
                    <div class="devis-field">
                        <label for="<?= $fid ?>"><?= e($field['label']) ?><?= $optTag ?></label>
                        <?php if ($field['type'] === 'select'): ?>
                        <select id="<?= $fid ?>" name="<?= e($field['key']) ?>"<?= $req ?>>
                            <option value="" disabled selected>Choisissez…</option>
                            <?php foreach ($field['options'] as $opt): ?>
                            <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="<?= $fid ?>" name="<?= e($field['key']) ?>" rows="4" maxlength="5000"<?= $req ?><?= $field['minlength'] > 0 ? ' minlength="' . (int) $field['minlength'] . '"' : '' ?>></textarea>
                        <?php else: ?>
                        <input type="<?= e($field['type']) ?>" id="<?= $fid ?>" name="<?= e($field['key']) ?>" maxlength="255"<?= $req ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($i === $devisLast): ?>
                    <div class="devis-consent">
                        <input type="checkbox" id="devis-consent" name="consent" value="1" required>
                        <label for="devis-consent">
                            <?= e($devisConsent !== '' ? $devisConsent : "J'accepte que mes données soient utilisées pour traiter ma demande.") ?>
                            <?php if ($devisPrivacy !== ''): ?>
                            <a href="<?= e(site_url($devisPrivacy)) ?>" target="_blank" rel="noopener">En savoir plus</a>.
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endif; ?>
                </fieldset>
                <?php endforeach; ?>

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
