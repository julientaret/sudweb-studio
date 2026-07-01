/*
 * Source JS du site public. Bundle + minify vers public/assets/site.js par
 * esbuild : `npm run build` (ou `npm run watch`).
 *
 * Chargé en fin de page via <script src="…/assets/site.js" defer data-aos-duration="…">
 * (footer.php). AOS (vendor/aos, cf. footer.php) est chargé juste avant en
 * script classique : window.AOS est donc déjà disponible ici. La durée vient
 * d'un attribut plutôt que d'un script inline (bloqué par la CSP `script-src`).
 */

import { initDevis } from './devis.js';

initDevis();

if (window.AOS) {
    var duration = parseInt(document.currentScript?.dataset.aosDuration, 10);
    window.AOS.init({ once: true, duration: isNaN(duration) ? 800 : duration });
}
