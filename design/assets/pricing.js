/* =========================================================
   Sud Web Studio — Tarifs · billing toggle + FAQ + reveals
   Runs after alt-main.js (cursor, trail, magnetic, nav, counters)
   ========================================================= */
(function () {
  "use strict";
  const hasGSAP = typeof window.gsap !== "undefined";
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const gsap = window.gsap;

  /* ---------- number formatting ---------- */
  const fmt = n => Math.round(n).toLocaleString("fr-FR").replace(/\u00A0/g, "\u202F");

  /* ---------- BILLING TOGGLE ---------- */
  const toggle = document.getElementById("billingToggle");
  const pricing = document.querySelector(".pricing");
  const note = document.getElementById("billingNote");
  const buttons = toggle ? [...toggle.querySelectorAll("button")] : [];

  const NOTES = {
    once: 'Vous devenez <b>propriétaire</b> dès la livraison',
    loa:  'Loyer tout inclus — en fin de formule, <b>le site vous appartient entièrement, sans frais caché</b>'
  };

  function animateAmount(el, target) {
    if (!hasGSAP || reduce) { el.textContent = fmt(target); return; }
    const from = parseFloat((el.textContent || "0").replace(/[^\d]/g, "")) || 0;
    const obj = { v: from };
    gsap.to(obj, {
      v: target, duration: .7, ease: "power2.out",
      onUpdate: () => { el.textContent = fmt(obj.v); }
    });
  }

  function setMode(mode) {
    if (!toggle) return;
    toggle.classList.toggle("loa", mode === "loa");
    if (pricing) pricing.setAttribute("data-billing", mode);
    buttons.forEach(b => b.setAttribute("aria-pressed", String(b.dataset.mode === mode)));
    if (note) note.innerHTML = NOTES[mode];

    document.querySelectorAll(".tier").forEach(tier => {
      const amt = tier.querySelector(".amount");
      const per = tier.querySelector(".per");
      const pre = tier.querySelector(".price-pre");
      const sub = tier.querySelector(".price-sub");
      if (amt) {
        const raw = amt.getAttribute(mode === "loa" ? "data-loa" : "data-once") || "";
        const num = parseFloat(raw.replace(/[^\d.]/g, ""));
        if (!isNaN(num)) animateAmount(amt, num);
        else amt.textContent = raw;
      }
      if (per) per.textContent = per.getAttribute(mode === "loa" ? "data-per-loa" : "data-per-once") || per.textContent;
      if (pre) pre.textContent = pre.getAttribute(mode === "loa" ? "data-pre-loa" : "data-pre-once") || pre.textContent;
      if (sub) sub.innerHTML = sub.getAttribute(mode === "loa" ? "data-sub-loa" : "data-sub-once") || sub.innerHTML;
    });

    try { localStorage.setItem("sws-billing", mode); } catch (e) {}
  }

  if (toggle) {
    buttons.forEach(b => b.addEventListener("click", () => setMode(b.dataset.mode)));
    let initial = "once";
    try { initial = localStorage.getItem("sws-billing") || "once"; } catch (e) {}
    // set silently without count animation on first paint
    if (initial === "loa") setMode("loa");
  }

  /* ---------- HEADER / SECTION line-mask reveals (not covered by alt-main) ---------- */
  if (hasGSAP && !reduce) {
    gsap.registerPlugin(window.ScrollTrigger);
    gsap.utils.toArray(".price-hero h1 .line-mask > *").forEach((el, i) => {
      gsap.to(el, { y: "0%", duration: 1.05, ease: "power4.out", delay: .1 + i * .12 });
    });
    gsap.utils.toArray(".loa-head h2 .line-mask > *, .faq-intro h2 .line-mask > *, .modules-head h2 .line-mask > *").forEach(el => {
      gsap.to(el, {
        y: "0%", duration: 1.05, ease: "power4.out",
        scrollTrigger: { trigger: el.closest("h2"), start: "top 88%" }
      });
    });

    /* tier cards staggered entrance */
    /* Entrance helper — static initial state (overridable, never a held from-tween)
       + onEnter reveal + immediate reveal if already in/above viewport on init. */
    function entrance(el, y, dur, ease, delay) {
      gsap.set(el, { opacity: 0, y: y });
      const show = () => gsap.to(el, { opacity: 1, y: 0, duration: dur, ease: ease, delay: delay || 0, overwrite: "auto", clearProps: "transform" });
      window.ScrollTrigger.create({ trigger: el, start: "top 92%", once: true, onEnter: show });
      if (el.getBoundingClientRect().top < window.innerHeight * 0.92) show();
    }

    gsap.utils.toArray(".price-grid .tier").forEach((el, i) => entrance(el, 36, .9, "power3.out", (i % 3) * .1));
    gsap.utils.toArray(".loa-step").forEach((el, i) => entrance(el, 32, .85, "power3.out", (i % 3) * .1));
    gsap.utils.toArray(".compare tbody tr").forEach((el, i) => entrance(el, 18, .6, "power2.out", (i % 8) * .05));

    /* safety net: reveal anything still hidden after load (throttled tab, etc.) */
    const safety = () => {
      document.querySelectorAll(".price-grid .tier, .loa-step, .compare tbody tr").forEach(el => {
        if (parseFloat(getComputedStyle(el).opacity) < 0.05) {
          gsap.to(el, { opacity: 1, y: 0, duration: .4, overwrite: "auto", clearProps: "transform" });
        }
      });
    };
    window.addEventListener("load", () => { window.ScrollTrigger.refresh(); setTimeout(safety, 800); });
    document.addEventListener("DOMContentLoaded", () => setTimeout(safety, 800));
    setTimeout(safety, 1500);

    /* bulletproof: any entrance element actually on-screen but still hidden
       (jump nav, anchor link, trigger mis-measure) is revealed immediately */
    const entranceEls = () => document.querySelectorAll(".price-grid .tier, .loa-step, .compare tbody tr");
    const revealInView = () => {
      entranceEls().forEach(el => {
        const r = el.getBoundingClientRect();
        const inView = r.top < window.innerHeight * 0.96 && r.bottom > 0;
        if (inView && parseFloat(getComputedStyle(el).opacity) < 0.05) {
          gsap.to(el, { opacity: 1, y: 0, duration: .55, ease: "power2.out", overwrite: "auto", clearProps: "transform" });
        }
      });
    };
    window.addEventListener("scroll", revealInView, { passive: true });
    window.addEventListener("resize", revealInView, { passive: true });
  }

  /* ---------- FAQ accordion ---------- */
  document.querySelectorAll(".faq-item").forEach(item => {
    const q = item.querySelector(".faq-q");
    const a = item.querySelector(".faq-a");
    if (!q || !a) return;
    q.addEventListener("click", () => {
      const open = item.classList.contains("open");
      // close siblings
      document.querySelectorAll(".faq-item.open").forEach(other => {
        if (other === item) return;
        other.classList.remove("open");
        animateFaq(other.querySelector(".faq-a"), false);
      });
      item.classList.toggle("open", !open);
      animateFaq(a, !open);
    });
  });

  function animateFaq(a, open) {
    if (!a) return;
    const target = open ? a.firstElementChild.offsetHeight : 0;
    if (!hasGSAP || reduce) { a.style.height = open ? "auto" : "0px"; return; }
    gsap.to(a, { height: target, duration: .5, ease: "power3.out",
      onComplete: () => { if (open) a.style.height = "auto"; } });
  }

  if (hasGSAP && window.ScrollTrigger) window.ScrollTrigger.refresh();
})();
