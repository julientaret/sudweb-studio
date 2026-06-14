/*
 * Source JS du site public. Bundle + minify vers public/assets/site.js par
 * esbuild : `npm run build` (ou `npm run watch`).
 *
 * Chargé en fin de page via <script src="…/assets/site.js" defer> (footer.php).
 */

import { initDevis } from './devis.js';

initDevis();
