<?php
/**
 * Pied de page commun et fermeture du document HTML du site public.
 *
 * Valeurs de gabarit (navigation, copyright, liens sociaux) issues de la config
 * centralisée frontend/config/site.php, via site(). Les liens vers les pages
 * légales viennent, eux, du module LegalPages (publiées dans l'admin).
 */

require_once __DIR__ . '/helpers.php';

$siteName  = site('name', 'Sud Web Studio');
$copyright = (string) (site('footer', [])['copyright'] ?? $siteName);
$nav       = (array) site('nav', []);
$social    = (array) site('social', []);

// Pictogrammes des réseaux reconnus. Un libellé inconnu reste affiché en texte.
$socialIcons = [
    'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
    'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
    'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
    'dribbble'  => '<circle cx="12" cy="12" r="10"/><path d="M19.13 5.09C15.22 9.14 10 10.44 2.25 10.94M21.75 12.84c-6.62-1.41-12.14 1-16.38 6.32M8.56 2.75c4.37 6 6 9.42 8 17.72"/>',
    'behance'   => '<path d="M2 7h5.5a2.5 2.5 0 0 1 0 5H2zM2 12h6a2.5 2.5 0 0 1 0 5H2zM15 9h6M14.5 14h7a3.5 3.5 0 0 0-7 0 3.5 3.5 0 0 0 6 2.5"/>',
];

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
    <footer class="foot<?= module_enabled('Devis') ? ' foot--fab' : '' ?>">
        <div class="shell">
            <div class="foot-bar">
                <a class="brand" href="<?= e(site_url('/')) ?>" aria-label="<?= e($siteName) ?> — accueil">
                    <span class="mk"><svg aria-hidden="true" focusable="false"><use href="#sws-mark"></use></svg></span>
                    <span class="wm"><?= e($siteName) ?></span>
                </a>

                <?php if ($nav): ?>
                <nav class="foot-nav" aria-label="Pied de page">
                    <?php foreach ($nav as $label => $url): ?>
                    <a href="<?= e(site_url($url)) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <?php if ($social): ?>
                <div class="foot-social">
                    <?php foreach ($social as $label => $url):
                        $icon = $socialIcons[strtolower((string) $label)] ?? null; ?>
                    <a href="<?= e($url) ?>" rel="noopener me" target="_blank" aria-label="<?= e($label) ?>">
                        <?php if ($icon !== null): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icon ?></svg>
                        <?php else: ?>
                        <?= e($label) ?>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="foot-base">
                <span>&copy; <span data-year><?= date('Y') ?></span> <?= e($copyright) ?> — Tous droits réservés</span>
                <?php if ($legalLinks): ?>
                <nav class="links" aria-label="Pages légales">
                    <?php foreach ($legalLinks as $link): ?>
                    <a href="<?= e(site_url('/' . $link['slug'])) ?>"><?= e($link['title']) ?></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>
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

    <script src="<?= e(asset_url('/assets/vendor/aos/aos.js')) ?>" defer></script>
    <script src="<?= e(asset_url('/assets/site.js')) ?>" defer data-aos-duration="<?= e((string) ($aosDuration ?? 800)) ?>"></script>
</body>
</html>
