/* =========================================================
   Sud Web Studio — Alt · premium motion (GSAP + ScrollTrigger)
   ========================================================= */
(function () {
  "use strict";
  const root = document.documentElement;
  root.classList.add("js");
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const hasGSAP = typeof window.gsap !== "undefined";

  /* ---------- YEAR ---------- */
  document.querySelectorAll("[data-year]").forEach(el => el.textContent = new Date().getFullYear());

  /* ---------- NAV scroll state ---------- */
  const nav = document.querySelector(".nav");
  const onScroll = () => nav && nav.classList.toggle("scrolled", window.scrollY > 24);
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  if (!hasGSAP || reduce) {
    // graceful fallback: show everything
    document.querySelectorAll(".reveal").forEach(e => { e.style.opacity = 1; e.style.transform = "none"; });
    document.querySelectorAll(".line-mask > *").forEach(e => { e.style.transform = "none"; });
    document.querySelectorAll(".bubble").forEach(e => { e.style.opacity = 0.4; });
    return;
  }

  const { gsap } = window;
  gsap.registerPlugin(window.ScrollTrigger);
  root.classList.add("js-ready");

  /* ---------- CUSTOM CURSOR ---------- */
  (function cursor() {
    if (window.matchMedia("(hover: none)").matches) return;
    const el = document.createElement("div");
    el.className = "cursor";
    el.innerHTML = '<div class="ring"></div><div class="dot"></div>';
    document.body.appendChild(el);
    const ring = el.querySelector(".ring"), dot = el.querySelector(".dot");
    const xrTo = gsap.quickTo(ring, "left", { duration: .5, ease: "power3" });
    const yrTo = gsap.quickTo(ring, "top", { duration: .5, ease: "power3" });
    const xdTo = gsap.quickTo(dot, "left", { duration: .12, ease: "power3" });
    const ydTo = gsap.quickTo(dot, "top", { duration: .12, ease: "power3" });
    window.addEventListener("mousemove", e => {
      xrTo(e.clientX); yrTo(e.clientY); xdTo(e.clientX); ydTo(e.clientY);
    });
    document.querySelectorAll("a, button, .work-card, [data-magnetic]").forEach(t => {
      t.addEventListener("mouseenter", () => el.classList.add("hot"));
      t.addEventListener("mouseleave", () => el.classList.remove("hot"));
    });
  })();

  /* ---------- ORANGE SMOKE TRAIL ---------- */
  (function trail() {
    if (window.matchMedia("(hover: none)").matches) return;
    const canvas = document.createElement("canvas");
    canvas.className = "trail-canvas";
    Object.assign(canvas.style, {
      position: "fixed", inset: "0", zIndex: "9997",
      pointerEvents: "none", width: "100%", height: "100%",
      filter: "blur(7px)"
    });
    document.body.appendChild(canvas);
    const ctx = canvas.getContext("2d");
    let dpr = Math.min(window.devicePixelRatio || 1, 2);
    function resize() {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = innerWidth * dpr; canvas.height = innerHeight * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    resize();
    window.addEventListener("resize", resize);

    const puffs = [];          // {x,y,vx,vy,r,life,max}
    let mx = -100, my = -100, px = -100, py = -100, has = false;
    window.addEventListener("mousemove", e => {
      if (!has) { px = e.clientX; py = e.clientY; }
      mx = e.clientX; my = e.clientY; has = true;
    });

    function loop() {
      ctx.clearRect(0, 0, innerWidth, innerHeight);

      if (has) {
        // distance moved → how much smoke to emit
        const dx = mx - px, dy = my - py;
        const dist = Math.hypot(dx, dy);
        const n = Math.min(6, Math.floor(dist / 6) + 1);
        for (let i = 0; i < n; i++) {
          const t = i / n;
          puffs.push({
            x: px + dx * t + (Math.random() - .5) * 10,
            y: py + dy * t + (Math.random() - .5) * 10,
            vx: dx * 0.04 + (Math.random() - .5) * 0.5,
            vy: dy * 0.04 + (Math.random() - .5) * 0.5 - 0.35, // slight rise
            r: 7 + Math.random() * 10,
            life: 0,
            max: 60 + Math.random() * 50
          });
        }
        px = mx; py = my;
      }
      if (puffs.length > 240) puffs.splice(0, puffs.length - 240);

      ctx.globalCompositeOperation = "lighter";
      for (let i = puffs.length - 1; i >= 0; i--) {
        const p = puffs[i];
        p.life++;
        p.x += p.vx; p.y += p.vy;
        p.vx *= 0.96; p.vy *= 0.96;
        const k = p.life / p.max;            // 0 → 1
        if (k >= 1) { puffs.splice(i, 1); continue; }
        const rad = p.r * (1 + k * 2.4);      // expand like smoke
        const alpha = Math.sin(k * Math.PI) * 0.1; // fade in then out
        const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, rad);
        g.addColorStop(0, `rgba(240,180,141,${alpha.toFixed(3)})`);
        g.addColorStop(0.5, `rgba(222,142,90,${(alpha * 0.5).toFixed(3)})`);
        g.addColorStop(1, "rgba(222,142,90,0)");
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.arc(p.x, p.y, rad, 0, Math.PI * 2); ctx.fill();
      }
      ctx.globalCompositeOperation = "source-over";
      requestAnimationFrame(loop);
    }
    loop();
  })();

  /* ---------- MAGNETIC ELEMENTS ---------- */
  document.querySelectorAll("[data-magnetic]").forEach(m => {
    const strength = parseFloat(m.dataset.magnetic) || 0.3;
    const xTo = gsap.quickTo(m, "--bx", { duration: .5, ease: "power3" });
    const yTo = gsap.quickTo(m, "--by", { duration: .5, ease: "power3" });
    m.addEventListener("mousemove", e => {
      const r = m.getBoundingClientRect();
      xTo(((e.clientX - r.left) / r.width - .5) * r.width * strength + "px");
      yTo(((e.clientY - r.top) / r.height - .5) * r.height * strength + "px");
    });
    m.addEventListener("mouseleave", () => { xTo("0px"); yTo("0px"); });
  });

  /* ---------- HERO entrance ---------- */
  const heroTl = gsap.timeline({ defaults: { ease: "power4.out" } });
  heroTl
    .to(".hero .eyebrow", { opacity: 1, y: 0, duration: .7 }, .1)
    .to(".hero h1 .line-mask > *", { y: "0%", duration: 1.05, stagger: .12 }, "<.05")
    .to(".hero-sub", { opacity: 1, y: 0, duration: .9 }, "-=.55")
    .to(".hero-actions", { opacity: 1, y: 0, duration: .8 }, "-=.6")
    .to(".hero-proof", { opacity: 1, y: 0, duration: .8 }, "-=.6")
    .fromTo(".hero-stage", { opacity: 0, scale: .9, y: 28 }, { opacity: 1, scale: 1, y: 0, duration: 1.3, ease: "expo.out" }, .25)
    .fromTo(".bubble", { opacity: 0, scale: .6, y: 14 }, { opacity: .4, scale: 1, y: 0, duration: .7, stagger: .09, ease: "back.out(1.7)" }, "-=.7")
    .to(".scroll-cue", { opacity: 1, duration: .6 }, "-=.4");

  /* ---------- HERO 3D logo — idle float, mouse parallax, scroll rotate ---------- */
  const logo = document.querySelector(".hero-logo");
  const glow = document.querySelector(".hero-stage .glow");
  if (logo) {
    gsap.to(logo, { y: "+=14", rotation: 1.5, duration: 4.5, ease: "sine.inOut", yoyo: true, repeat: -1 });
    gsap.to(glow, { scale: 1.12, opacity: .8, duration: 3.2, ease: "sine.inOut", yoyo: true, repeat: -1 });

    const stage = document.querySelector(".hero-stage");
    const rxTo = gsap.quickTo(logo, "rotationY", { duration: .8, ease: "power3" });
    const ryTo = gsap.quickTo(logo, "rotationX", { duration: .8, ease: "power3" });
    const pxTo = gsap.quickTo(logo, "x", { duration: .9, ease: "power3" });
    gsap.set(logo, { transformPerspective: 900, transformOrigin: "50% 50%" });
    window.addEventListener("mousemove", e => {
      const cx = (e.clientX / window.innerWidth - .5);
      const cy = (e.clientY / window.innerHeight - .5);
      rxTo(cx * 22); ryTo(-cy * 16); pxTo(cx * 18);
    });
    // scroll: drift + rotate the whole stage
    gsap.to(".hero-stage", {
      yPercent: 16, rotation: 8, ease: "none",
      scrollTrigger: { trigger: ".hero", start: "top top", end: "bottom top", scrub: 1 }
    });
  }

  // per-bubble idle float — fully predefined, randomized drift
  gsap.utils.toArray(".bubble").forEach((b, i) => {
    gsap.to(b, {
      y: "+=" + (14 + (i % 3) * 8),
      duration: 3 + (i % 4) * 0.7, ease: "sine.inOut", yoyo: true, repeat: -1,
      delay: 0.9 + i * 0.18
    });
    gsap.to(b, {
      x: "+=" + (i % 2 ? 12 : -12),
      duration: 4.5 + (i % 3) * 0.9, ease: "sine.inOut", yoyo: true, repeat: -1,
      delay: 1.1 + i * 0.14
    });
    gsap.to(b, {
      rotation: i % 2 ? 3 : -3,
      duration: 5 + (i % 4) * 0.8, ease: "sine.inOut", yoyo: true, repeat: -1,
      delay: i * 0.2
    });
    b.addEventListener("mouseenter", () => gsap.to(b, { scale: 1.14, opacity: 1, duration: .4, ease: "back.out(2)", overwrite: "auto" }));
    b.addEventListener("mouseleave", () => gsap.to(b, { scale: 1, opacity: .4, duration: .5, ease: "power3.out", overwrite: "auto" }));
  });

  /* ---------- GENERIC reveals ---------- */
  gsap.utils.toArray(".reveal").forEach(el => {
    if (el.closest(".hero")) return;
    gsap.to(el, {
      opacity: 1, y: 0, duration: 1, ease: "power3.out",
      scrollTrigger: { trigger: el, start: "top 86%" }
    });
  });

  /* ---------- Section heading line masks ---------- */
  gsap.utils.toArray(".sec-head h2 .line-mask > *, .work-head h2 .line-mask > *, .exp-intro h2 .line-mask > *, .local-copy h2 .line-mask > *, .clients-head h2 .line-mask > *, .cta-body h2 .line-mask > *").forEach(el => {
    gsap.to(el, {
      y: "0%", duration: 1.1, ease: "power4.out",
      scrollTrigger: { trigger: el.closest("h2"), start: "top 88%" }
    });
  });

  /* ---------- EXPERTISE cards stagger ---------- */
  gsap.from(".exp-card", {
    opacity: 0, y: 40, duration: .9, ease: "power3.out", stagger: .1,
    scrollTrigger: { trigger: ".exp-grid", start: "top 82%" }
  });

  /* ---------- WORK cards reveal + image parallax ---------- */
  gsap.utils.toArray(".work-card").forEach((card, i) => {
    gsap.from(card, {
      opacity: 0, y: 60, scale: .97, duration: 1, ease: "power3.out",
      scrollTrigger: { trigger: card, start: "top 88%" }
    });
    const shot = card.querySelector(".shot");
    if (shot) {
      gsap.fromTo(shot, { yPercent: -6 }, {
        yPercent: 6, ease: "none",
        scrollTrigger: { trigger: card, start: "top bottom", end: "bottom top", scrub: true }
      });
    }
  });

  /* ---------- MARQUEE loop ---------- */
  const marquee = document.querySelector(".marquee");
  if (marquee) {
    const w = marquee.scrollWidth / 2;
    gsap.to(marquee, { x: -w, duration: 22, ease: "none", repeat: -1, modifiers: { x: gsap.utils.unitize(x => parseFloat(x) % w) } });
  }

  /* ---------- COUNTERS ---------- */
  gsap.utils.toArray("[data-to]").forEach(el => {
    const to = parseFloat(el.dataset.to);
    const obj = { v: 0 };
    ScrollTrigger.create({
      trigger: el, start: "top 90%", once: true,
      onEnter: () => gsap.to(obj, { v: to, duration: 1.6, ease: "power2.out", onUpdate: () => el.textContent = Math.round(obj.v) })
    });
  });

  /* ---------- Local map: idle float + scroll parallax + trace draw ---------- */
  const map = document.querySelector(".map-canvas");
  if (map) {
    gsap.to(map, { y: "+=16", duration: 5, ease: "sine.inOut", yoyo: true, repeat: -1 });
    gsap.fromTo(map, { yPercent: 8 }, {
      yPercent: -8, ease: "none",
      scrollTrigger: { trigger: ".local", start: "top bottom", end: "bottom top", scrub: 1 }
    });
    const trace = map.querySelector(".map-trace");
    if (trace) {
      ScrollTrigger.create({
        trigger: map, start: "top 75%", once: true,
        onEnter: () => trace.classList.add("drawn")
      });
    }
  }

  /* ---------- CTA logo parallax ---------- */
  const ctaLogo = document.querySelector(".cta-logo img");
  if (ctaLogo) {
    gsap.fromTo(ctaLogo, { rotation: -8, y: 30 }, {
      rotation: 4, y: -20, ease: "none",
      scrollTrigger: { trigger: ".cta", start: "top bottom", end: "bottom top", scrub: 1 }
    });
  }

  /* ---------- NAV active link ---------- */
  document.querySelectorAll("main section[id]").forEach(sec => {
    ScrollTrigger.create({
      trigger: sec, start: "top 40%", end: "bottom 40%",
      onToggle: self => {
        const link = document.querySelector('.nav-links a[href="#' + sec.id + '"]');
        if (link && self.isActive) {
          document.querySelectorAll(".nav-links a").forEach(a => a.classList.remove("active"));
          link.classList.add("active");
        }
      }
    });
  });

  ScrollTrigger.refresh();
})();
