<?php
/**
 * Carte d'appel à l'action de fin de page, partagée par les pages du site.
 *
 * Le bouton ouvre la modale du module Devis quand il est actif, et retombe
 * sinon sur la page de contact : une seule définition de cette bascule pour
 * tout le site.
 *
 * @var string $ctaEyebrow  surtitre (optionnel)
 * @var string $ctaTitle    titre de la carte (optionnel)
 * @var string $ctaText     paragraphe (optionnel)
 */

require_once __DIR__ . '/helpers.php';

$ctaEyebrow = $ctaEyebrow ?? 'Prêt à démarrer&nbsp;?';
$ctaTitle   = $ctaTitle   ?? 'Parlons de votre projet';
$ctaText    = $ctaText    ?? 'Discutons de vos idées et construisons ensemble un site web à la hauteur de vos ambitions.';
$ctaLabel   = (string) ((array) site('cta', []))['label'] ?? 'Discutons de votre projet';
?>
<section class="section cta" id="contact">
    <div class="shell">
        <div class="cta-card">
            <div class="cta-logo">
                <img src="<?= e(asset_url('/assets/img/logo3d-hero.webp')) ?>" alt="" aria-hidden="true"
                     loading="lazy" width="1254" height="1254">
            </div>
            <div class="cta-body">
                <span class="eyebrow"><?= $ctaEyebrow ?></span>
                <h2><span class="line-mask"><span><?= $ctaTitle ?></span></span></h2>
                <p><?= $ctaText ?></p>
            </div>
            <div class="cta-action">
                <?php if (module_enabled('Devis')): ?>
                <button type="button" class="btn btn-primary" data-devis-open data-magnetic="0.3">
                    <?= e($ctaLabel) ?> <span class="ico" aria-hidden="true">↗</span>
                </button>
                <?php else: ?>
                <a class="btn btn-primary" href="<?= e(site_url('/contact')) ?>" data-magnetic="0.3">
                    <?= e($ctaLabel) ?> <span class="ico" aria-hidden="true">↗</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
