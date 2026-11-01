/*
 * En-tête : état « scrolled » de la barre fixe et menu plein écran mobile.
 *
 * Volontairement sans GSAP — la navigation doit fonctionner même si la couche
 * motion est désactivée (prefers-reduced-motion) ou absente.
 */

export function initNav() {
    const nav = document.querySelector('.nav');
    if (!nav) return;

    // Teinte la barre dès que la page a défilé.
    const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 24);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const burger = nav.querySelector('.nav-burger');
    const panel = document.querySelector('.nav-panel');
    if (!burger || !panel) return;

    const setOpen = (open) => {
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    setOpen(false);
    burger.addEventListener('click', () => {
        setOpen(burger.getAttribute('aria-expanded') !== 'true');
    });

    // Fermeture : clic sur un lien du panneau, Échap, ou retour au desktop.
    panel.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            burger.focus();
        }
    });
    window.matchMedia('(min-width: 821px)').addEventListener('change', (e) => {
        if (e.matches) setOpen(false);
    });
}
