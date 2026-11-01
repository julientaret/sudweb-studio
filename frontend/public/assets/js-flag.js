/*
 * Marque le document comme « JS actif », avant tout rendu.
 *
 * Le CSS ne masque les états de départ des animations (.reveal, .line-mask,
 * .bubble) que sous html.js : sans JS, la page est lisible telle quelle, et
 * sans ce drapeau posé tôt on verrait le contenu apparaître puis disparaître.
 *
 * Fichier autonome et non différé plutôt que script inline : la CSP du noyau
 * est en script-src 'self' (App\Core\Kernel::sendSecurityHeaders).
 */
document.documentElement.className += " js";
