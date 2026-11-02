/*
 * Couche motion du site (GSAP 3 + ScrollTrigger), portée depuis la maquette
 * design/assets/alt-main.js.
 *
 * GSAP est bundlé depuis node_modules (pas de CDN) : une seule requête, et le
 * site reste servable sans réseau tiers.
 *
 * Contrat : le site doit être entièrement lisible SANS cette couche. Les états
 * de départ (.reveal, .line-mask > *, .bubble) ne sont masqués que si <html> a
 * la classe `js` ; si prefers-reduced-motion est actif, initMotion() se contente
 * de tout révéler et s'arrête là.
 */

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

/** Révèle tout ce que la couche motion aurait animé (filet de sécurité). */
function revealAll() {
    document.querySelectorAll('.reveal').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
    });
    document.querySelectorAll('.line-mask > *').forEach((el) => {
        el.style.transform = 'none';
    });
    document.querySelectorAll('.bubble').forEach((el) => {
        el.style.opacity = '0.4';
    });
}

export function initMotion() {
    const root = document.documentElement;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealAll();
        return;
    }

    gsap.registerPlugin(ScrollTrigger);
    root.classList.add('js-ready');

    // Exposés aux scripts de page : le filtre des Réalisations doit recalculer
    // les positions de déclenchement, le décompte des tarifs veut animer un
    // nombre. Ils s'en passent si la couche motion ne tourne pas.
    window.gsap = gsap;
    window.ScrollTrigger = ScrollTrigger;

    cursor();
    smokeTrail();
    magnetic();
    hero();
    pageHero();
    reveals();
    expertiseCards();
    workCards();
    marquee();
    localMap();
    ctaLogo();
    activeNavLink();

    ScrollTrigger.refresh();
}

/* ---------- curseur personnalisé (point + anneau) ---------- */
function cursor() {
    if (window.matchMedia('(hover: none)').matches) return;

    const el = document.createElement('div');
    el.className = 'cursor';
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML = '<div class="ring"></div><div class="dot"></div>';
    document.body.appendChild(el);

    const ring = el.querySelector('.ring');
    const dot = el.querySelector('.dot');
    const xr = gsap.quickTo(ring, 'left', { duration: 0.5, ease: 'power3' });
    const yr = gsap.quickTo(ring, 'top', { duration: 0.5, ease: 'power3' });
    const xd = gsap.quickTo(dot, 'left', { duration: 0.12, ease: 'power3' });
    const yd = gsap.quickTo(dot, 'top', { duration: 0.12, ease: 'power3' });

    window.addEventListener('mousemove', (e) => {
        xr(e.clientX); yr(e.clientY); xd(e.clientX); yd(e.clientY);
    });

    // Anneau élargi au survol de tout ce qui est cliquable.
    document.querySelectorAll('a, button, .work-card, [data-magnetic]').forEach((t) => {
        t.addEventListener('mouseenter', () => el.classList.add('hot'));
        t.addEventListener('mouseleave', () => el.classList.remove('hot'));
    });
}

/* ---------- traînée de fumée pêche derrière le curseur ---------- */
function smokeTrail() {
    if (window.matchMedia('(hover: none)').matches) return;

    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    Object.assign(canvas.style, {
        position: 'fixed', inset: '0', zIndex: '9997',
        pointerEvents: 'none', width: '100%', height: '100%',
        filter: 'blur(7px)',
    });
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    const resize = () => {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = innerWidth * dpr;
        canvas.height = innerHeight * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);

    const puffs = [];
    let mx = -100, my = -100, px = -100, py = -100;
    let moved = false;      // la souris a bougé depuis la dernière image
    let running = false;    // une boucle rAF est en cours

    window.addEventListener('mousemove', (e) => {
        if (px < 0) { px = e.clientX; py = e.clientY; }
        mx = e.clientX; my = e.clientY; moved = true;
        if (!running) { running = true; requestAnimationFrame(loop); }
    });

    function loop() {
        ctx.clearRect(0, 0, innerWidth, innerHeight);

        if (moved) {
            // distance parcourue → quantité de fumée émise
            const dx = mx - px, dy = my - py;
            const n = Math.min(6, Math.floor(Math.hypot(dx, dy) / 6) + 1);
            for (let i = 0; i < n; i++) {
                const t = i / n;
                puffs.push({
                    x: px + dx * t + (Math.random() - 0.5) * 10,
                    y: py + dy * t + (Math.random() - 0.5) * 10,
                    vx: dx * 0.04 + (Math.random() - 0.5) * 0.5,
                    vy: dy * 0.04 + (Math.random() - 0.5) * 0.5 - 0.35, // légère montée
                    r: 7 + Math.random() * 10,
                    life: 0,
                    max: 60 + Math.random() * 50,
                });
            }
            px = mx; py = my; moved = false;
        }
        if (puffs.length > 240) puffs.splice(0, puffs.length - 240);

        ctx.globalCompositeOperation = 'lighter';
        for (let i = puffs.length - 1; i >= 0; i--) {
            const p = puffs[i];
            p.life++;
            p.x += p.vx; p.y += p.vy;
            p.vx *= 0.96; p.vy *= 0.96;
            const k = p.life / p.max;
            if (k >= 1) { puffs.splice(i, 1); continue; }
            const rad = p.r * (1 + k * 2.4);               // expansion
            const alpha = Math.sin(k * Math.PI) * 0.1;     // apparition puis fondu
            const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, rad);
            g.addColorStop(0, `rgba(240,180,141,${alpha.toFixed(3)})`);
            g.addColorStop(0.5, `rgba(222,142,90,${(alpha * 0.5).toFixed(3)})`);
            g.addColorStop(1, 'rgba(222,142,90,0)');
            ctx.fillStyle = g;
            ctx.beginPath(); ctx.arc(p.x, p.y, rad, 0, Math.PI * 2); ctx.fill();
        }
        ctx.globalCompositeOperation = 'source-over';

        // Souris immobile et plus aucune bouffée : on rend la main au navigateur
        // (le prochain mousemove relance la boucle) plutôt que de tourner à vide.
        if (puffs.length === 0) { running = false; return; }
        requestAnimationFrame(loop);
    }
}

/* ---------- boutons attirés par le curseur ---------- */
function magnetic() {
    document.querySelectorAll('[data-magnetic]').forEach((m) => {
        const strength = parseFloat(m.dataset.magnetic) || 0.3;
        const xTo = gsap.quickTo(m, '--bx', { duration: 0.5, ease: 'power3' });
        const yTo = gsap.quickTo(m, '--by', { duration: 0.5, ease: 'power3' });
        m.addEventListener('mousemove', (e) => {
            const r = m.getBoundingClientRect();
            xTo(((e.clientX - r.left) / r.width - 0.5) * r.width * strength + 'px');
            yTo(((e.clientY - r.top) / r.height - 0.5) * r.height * strength + 'px');
        });
        m.addEventListener('mouseleave', () => { xTo('0px'); yTo('0px'); });
    });
}

/* ---------- entrée du hero, logo 3D et bulles ---------- */
function hero() {
    if (!document.querySelector('.hero')) return;

    gsap.timeline({ defaults: { ease: 'power4.out' } })
        .to('.hero .eyebrow', { opacity: 1, y: 0, duration: 0.7 }, 0.1)
        .to('.hero h1 .line-mask > *', { y: '0%', duration: 1.05, stagger: 0.12 }, '<.05')
        .to('.hero-sub', { opacity: 1, y: 0, duration: 0.9 }, '-=.55')
        .to('.hero-actions', { opacity: 1, y: 0, duration: 0.8 }, '-=.6')
        .fromTo('.hero-stage', { opacity: 0, scale: 0.9, y: 28 },
            { opacity: 1, scale: 1, y: 0, duration: 1.3, ease: 'expo.out' }, 0.25)
        .fromTo('.bubble', { opacity: 0, scale: 0.6, y: 14 },
            { opacity: 0.4, scale: 1, y: 0, duration: 0.7, stagger: 0.09, ease: 'back.out(1.7)' }, '-=.7')
        .to('.scroll-cue', { opacity: 1, duration: 0.6 }, '-=.4');

    const logo = document.querySelector('.hero-logo');
    if (logo) {
        const glow = document.querySelector('.hero-stage .glow');
        gsap.to(logo, { y: '+=14', rotation: 1.5, duration: 4.5, ease: 'sine.inOut', yoyo: true, repeat: -1 });
        if (glow) gsap.to(glow, { scale: 1.12, opacity: 0.8, duration: 3.2, ease: 'sine.inOut', yoyo: true, repeat: -1 });

        // parallaxe souris
        const rx = gsap.quickTo(logo, 'rotationY', { duration: 0.8, ease: 'power3' });
        const ry = gsap.quickTo(logo, 'rotationX', { duration: 0.8, ease: 'power3' });
        const px = gsap.quickTo(logo, 'x', { duration: 0.9, ease: 'power3' });
        gsap.set(logo, { transformPerspective: 900, transformOrigin: '50% 50%' });
        window.addEventListener('mousemove', (e) => {
            const cx = e.clientX / window.innerWidth - 0.5;
            const cy = e.clientY / window.innerHeight - 0.5;
            rx(cx * 22); ry(-cy * 16); px(cx * 18);
        });

        // dérive + rotation de la scène au défilement
        gsap.to('.hero-stage', {
            yPercent: 16, rotation: 8, ease: 'none',
            scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 1 },
        });
    }

    // flottement propre à chaque bulle : dérive x/y/rotation, durées décalées
    gsap.utils.toArray('.bubble').forEach((b, i) => {
        gsap.to(b, {
            y: '+=' + (14 + (i % 3) * 8),
            duration: 3 + (i % 4) * 0.7, ease: 'sine.inOut', yoyo: true, repeat: -1,
            delay: 0.9 + i * 0.18,
        });
        gsap.to(b, {
            x: '+=' + (i % 2 ? 12 : -12),
            duration: 4.5 + (i % 3) * 0.9, ease: 'sine.inOut', yoyo: true, repeat: -1,
            delay: 1.1 + i * 0.14,
        });
        gsap.to(b, {
            rotation: i % 2 ? 3 : -3,
            duration: 5 + (i % 4) * 0.8, ease: 'sine.inOut', yoyo: true, repeat: -1,
            delay: i * 0.2,
        });
        b.addEventListener('mouseenter', () =>
            gsap.to(b, { scale: 1.14, opacity: 1, duration: 0.4, ease: 'back.out(2)', overwrite: 'auto' }));
        b.addEventListener('mouseleave', () =>
            gsap.to(b, { scale: 1, opacity: 0.4, duration: 0.5, ease: 'power3.out', overwrite: 'auto' }));
    });
}

/* ---------- titre des pages intérieures ---------- */
/*
 * Les pages intérieures ouvrent sur .work-hero, pas sur le .hero de l'accueil.
 * Leur titre est au-dessus de la ligne de flottaison : il se révèle au
 * chargement, pas au défilement comme les titres de section.
 */
function pageHero() {
    const lines = document.querySelectorAll('.page-hero h1 .line-mask > *');
    if (!lines.length) return;
    gsap.to(lines, { y: '0%', duration: 1.05, ease: 'power4.out', stagger: 0.12, delay: 0.15 });
}

/* ---------- fondus au défilement et titres masqués ligne par ligne ---------- */
function reveals() {
    gsap.utils.toArray('.reveal').forEach((el) => {
        if (el.closest('.hero')) return; // déjà pris en charge par la timeline du hero
        gsap.to(el, {
            opacity: 1, y: 0, duration: 1, ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 86%' },
        });
    });

    // Tout h2 contenant des .line-mask se révèle ligne par ligne au défilement.
    // Détecté par le contenu plutôt que par une liste de sélecteurs par section,
    // qu'il faudrait rallonger à chaque nouvelle page.
    const heads = new Set();
    document.querySelectorAll('h2 .line-mask').forEach((m) => heads.add(m.closest('h2')));
    heads.forEach((h2) => {
        // Les titres de page, eux, s'animent au chargement (cf. pageHero).
        if (h2.closest('.page-hero')) return;
        gsap.to(h2.querySelectorAll('.line-mask > *'), {
            y: '0%', duration: 1.1, ease: 'power4.out', stagger: 0.12,
            scrollTrigger: { trigger: h2, start: 'top 88%' },
        });
    });
}

function expertiseCards() {
    if (!document.querySelector('.exp-grid')) return;
    // fromTo plutôt que from : un ScrollTrigger.refresh() (images chargées,
    // fenêtre redimensionnée) relit les valeurs de départ d'un from sur l'état
    // courant et fige les cartes au milieu de leur course, donc décalées entre
    // elles. Les valeurs explicites + once évitent ce rejeu.
    gsap.fromTo('.exp-card', { opacity: 0, y: 40 }, {
        opacity: 1, y: 0, duration: 0.9, ease: 'power3.out', stagger: 0.1, overwrite: 'auto',
        scrollTrigger: { trigger: '.exp-grid', start: 'top 82%', once: true },
    });
}

function workCards() {
    gsap.utils.toArray('.work-card').forEach((card) => {
        gsap.fromTo(card, { opacity: 0, y: 60, scale: 0.97 }, {
            opacity: 1, y: 0, scale: 1, duration: 1, ease: 'power3.out', overwrite: 'auto',
            scrollTrigger: { trigger: card, start: 'top 88%', once: true },
        });
        const shot = card.querySelector('.shot');
        if (shot) {
            gsap.fromTo(shot, { yPercent: -6 }, {
                yPercent: 6, ease: 'none',
                scrollTrigger: { trigger: card, start: 'top bottom', end: 'bottom top', scrub: true },
            });
        }
    });
}

function marquee() {
    const el = document.querySelector('.marquee');
    if (!el) return;
    // Le contenu est dupliqué : on boucle sur la moitié de la largeur.
    const w = el.scrollWidth / 2;
    if (!w) return;
    gsap.to(el, {
        x: -w, duration: 22, ease: 'none', repeat: -1,
        modifiers: { x: gsap.utils.unitize((x) => parseFloat(x) % w) },
    });
}

function localMap() {
    const map = document.querySelector('.map-canvas');
    if (!map) return;

    gsap.to(map, { y: '+=16', duration: 5, ease: 'sine.inOut', yoyo: true, repeat: -1 });
    gsap.fromTo(map, { yPercent: 8 }, {
        yPercent: -8, ease: 'none',
        scrollTrigger: { trigger: '.local', start: 'top bottom', end: 'bottom top', scrub: 1 },
    });

    const trace = map.querySelector('.map-trace');
    if (trace) {
        ScrollTrigger.create({
            trigger: map, start: 'top 75%', once: true,
            onEnter: () => trace.classList.add('drawn'),
        });
    }
}

function ctaLogo() {
    const img = document.querySelector('.cta-logo img');
    if (!img) return;
    gsap.fromTo(img, { rotation: -8, y: 30 }, {
        rotation: 4, y: -20, ease: 'none',
        scrollTrigger: { trigger: '.cta', start: 'top bottom', end: 'bottom top', scrub: 1 },
    });
}

/* ---------- lien de nav actif selon la section visible ---------- */
/*
 * Les entrées de menu qui pointent vers une ancre ne sont jamais actives au
 * rendu (cf. nav_is_active() côté PHP) : c'est ici qu'elles s'allument, quand
 * leur section traverse l'écran.
 */
function activeNavLink() {
    const links = [...document.querySelectorAll('.nav-links a')];
    if (!links.length) return;

    // Entrée « page courante » (sans ancre) : elle reprend la main sur les
    // sections qu'aucune entrée ne désigne — le hero, en pratique.
    const pageLink = links.find((a) => !a.hash && a.pathname === location.pathname) || null;

    const setActive = (link) => {
        if (!link) return;
        links.forEach((a) => a.classList.remove('active'));
        link.classList.add('active');
    };

    document.querySelectorAll('main section[id]').forEach((sec) => {
        // L'ancre doit viser la page courante : sur une page intérieure,
        // « /#contact » renvoie à l'accueil et ne doit pas voler l'état actif
        // à l'entrée de la page où l'on se trouve.
        const link = links.find((a) => a.hash === '#' + sec.id && a.pathname === location.pathname)
            || pageLink;
        if (!link) return;
        ScrollTrigger.create({
            trigger: sec, start: 'top 40%', end: 'bottom 40%',
            onToggle: (self) => { if (self.isActive) setActive(link); },
        });
    });
}
