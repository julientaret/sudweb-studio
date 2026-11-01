/*
 * Page Réalisations : filtre par catégorie.
 *
 * Sans dépendance (pas de GSAP) — le filtre doit marcher même si la couche
 * motion est désactivée. No-op sur les autres pages.
 */

export function initRealisations() {
    const bar = document.querySelector('.filters');
    if (!bar) return;

    const buttons = [...bar.querySelectorAll('.filter-btn')];
    const projects = [...document.querySelectorAll('.proj')];
    const empty = document.querySelector('[data-work-empty]');
    if (!buttons.length || !projects.length) return;

    const apply = (filter) => {
        let shown = 0;
        projects.forEach((p) => {
            const match = filter === 'all' || p.dataset.cat === filter;
            p.hidden = !match;
            if (!match) return;
            // L'alternance visuel / texte se recompte sur les seules lignes
            // visibles : le rang posé au rendu ne vaut plus une fois filtré, et
            // deux lignes orientées pareil pourraient se suivre.
            p.classList.toggle('rev', shown % 2 === 1);
            shown++;
        });
        if (empty) empty.hidden = shown > 0;

        buttons.forEach((b) => {
            const on = b.dataset.filter === filter;
            b.classList.toggle('active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        // Les positions de déclenchement du scroll dépendent de la hauteur de
        // la page, qui vient de changer : sans ce recalcul, les lignes encore
        // visibles resteraient figées à leur état d'entrée.
        if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    };

    buttons.forEach((b) => b.addEventListener('click', () => apply(b.dataset.filter)));
}
