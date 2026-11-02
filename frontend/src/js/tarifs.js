/*
 * Page Tarifs : bascule paiement unique / LOA et accordéon de la FAQ.
 *
 * GSAP ne sert qu'au décompte des montants, et seulement s'il est là : sans
 * lui, ou sous prefers-reduced-motion, la valeur est posée directement. La
 * bascule et la FAQ, elles, ne dépendent de rien.
 *
 * No-op sur les autres pages.
 */

const STORAGE_KEY = 'sws-billing';

export function initTarifs() {
    billing();
    faq();
}

/* ---------- bascule paiement unique / LOA ---------- */
function billing() {
    const page = document.querySelector('[data-billing]');
    if (!page) return;

    const buttons = [...document.querySelectorAll('[data-billing-mode]')];
    if (!buttons.length) return;

    const setMode = (mode, animate) => {
        page.setAttribute('data-billing', mode);
        buttons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.billingMode === mode)));

        // Les libellés des deux modes coexistent dans le HTML et c'est le CSS
        // qui masque l'inactif ; seuls montant et unité changent de valeur.
        document.querySelectorAll('.tier .amount').forEach((el) => {
            setAmount(el, Number(el.dataset[mode]), animate);
        });
        document.querySelectorAll('.tier .per').forEach((el) => {
            el.textContent = el.dataset[mode] || el.textContent;
        });

        // Le choix suit le visiteur d'une page à l'autre. Échec silencieux :
        // navigation privée, stockage refusé — la bascule marche quand même.
        try { localStorage.setItem(STORAGE_KEY, mode); } catch { /* sans effet */ }
    };

    buttons.forEach((b) => b.addEventListener('click', () => setMode(b.dataset.billingMode, true)));

    // Filet : le décompte passe par requestAnimationFrame, que le navigateur
    // bride dans un onglet masqué — un montant à mi-chemin, qui n'est le prix
    // de rien, resterait affiché au retour. On le fige sur sa valeur exacte.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') return;
        const mode = page.getAttribute('data-billing');
        document.querySelectorAll('.tier .amount').forEach((el) => {
            setAmount(el, Number(el.dataset[mode]), false);
        });
    });

    let saved = null;
    try { saved = localStorage.getItem(STORAGE_KEY); } catch { /* sans effet */ }
    // Au premier rendu, pas de décompte : la page s'ouvre sur le bon montant.
    if (saved === 'loa') setMode('loa', false);
}

/** Montant formaté à la française, espace fine insécable en séparateur. */
function format(n) {
    return Math.round(n).toLocaleString('fr-FR').replace(/ /g, ' ');
}

function setAmount(el, target, animate) {
    if (!Number.isFinite(target)) return;

    const gsap = window.gsap;

    // Un décompte déjà en cours sur ce montant vise une valeur qui n'est plus
    // la bonne : on l'abandonne, sinon il écraserait celle qu'on pose ici.
    if (gsap && el.swsCounter) gsap.killTweensOf(el.swsCounter);

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!animate || !gsap || reduce) {
        el.textContent = format(target);
        return;
    }

    // Le séparateur de milliers est retiré avant lecture du point de départ.
    const from = Number((el.textContent || '0').replace(/\D/g, '')) || 0;
    const counter = { v: from };
    el.swsCounter = counter;
    gsap.to(counter, {
        v: target, duration: 0.7, ease: 'power2.out',
        onUpdate: () => { el.textContent = format(counter.v); },
        onComplete: () => { el.textContent = format(target); },
    });
}

/* ---------- accordéon de la FAQ ---------- */
/*
 * Le dépliage lui-même est en CSS (grid-template-rows 0fr → 1fr) : ici on ne
 * fait que basculer aria-expanded, dont le CSS se sert comme état.
 */
function faq() {
    const questions = [...document.querySelectorAll('.faq-q')];
    if (!questions.length) return;

    questions.forEach((q) => {
        q.addEventListener('click', () => {
            const open = q.getAttribute('aria-expanded') === 'true';
            // Une seule réponse ouverte à la fois.
            questions.forEach((other) => other.setAttribute('aria-expanded', 'false'));
            q.setAttribute('aria-expanded', open ? 'false' : 'true');
        });
    });
}
