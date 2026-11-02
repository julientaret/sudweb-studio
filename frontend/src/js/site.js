/*
 * Source JS du site public. Bundle + minify vers public/assets/site.js par
 * esbuild : `npm run build` (ou `npm run watch`).
 *
 * Chargé en fin de page via <script src="…/assets/site.js" defer data-aos-duration="…">
 * (footer.php). AOS (vendor/aos, cf. footer.php) est chargé juste avant en
 * script classique : window.AOS est donc déjà disponible ici. La durée vient
 * d'un attribut plutôt que d'un script inline (bloqué par la CSP `script-src`).
 */

import { initNav } from './nav.js';
import { initRealisations } from './realisations.js';
import { initTarifs } from './tarifs.js';
import { initDevisPage } from './devis-page.js';
import { initMotion } from './motion.js';

// Marque le document comme « JS actif » : le CSS n'applique les états de départ
// des animations (.reveal, .line-mask) que dans ce cas — sans JS, tout est visible.
document.documentElement.classList.add('js');

// Année courante du pied de page.
document.querySelectorAll('[data-year]').forEach((el) => {
    el.textContent = String(new Date().getFullYear());
});

initNav();
initRealisations();
initTarifs();
initDevisPage();
initMotion();

if (window.AOS) {
    var duration = parseInt(document.currentScript?.dataset.aosDuration, 10);
    window.AOS.init({ once: true, duration: isNaN(duration) ? 800 : duration });
}
