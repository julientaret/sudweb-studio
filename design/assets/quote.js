/* =========================================================
   Sud Web Studio — Demande de devis · live summary + submit
   Runs after alt-main.js (cursor, magnetic, nav, reveals)
   ========================================================= */
(function () {
  "use strict";
  const hasGSAP = typeof window.gsap !== "undefined";
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const gsap = window.gsap;
  const form = document.getElementById("quoteForm");
  if (!form) return;

  /* ---------- header line-mask reveal ---------- */
  if (hasGSAP && !reduce) {
    gsap.utils.toArray(".quote-hero h1 .line-mask > *").forEach((el, i) => {
      gsap.to(el, { y: "0%", duration: 1.05, ease: "power4.out", delay: .1 + i * .12 });
    });
  }

  /* ---------- live summary ---------- */
  const out = sel => document.querySelector('[data-out="' + sel + '"]');

  function setText(key, val) {
    const el = out(key);
    if (!el) return;
    if (val) { el.textContent = val; el.classList.remove("empty"); }
    else {
      el.textContent = el.dataset.placeholder || (key === "type" ? "À renseigner" : "—");
      el.classList.add("empty");
    }
  }

  function syncRadios(name) {
    const checked = form.querySelector('input[name="' + name + '"]:checked');
    setText(name, checked ? checked.dataset.label : "");
  }

  function syncModules() {
    const el = out("modules");
    if (!el) return;
    const checked = [...form.querySelectorAll('input[name="modules"]:checked')];
    if (!checked.length) {
      el.innerHTML = '<span class="v empty" style="text-align:left;">Aucune sélectionnée</span>';
      return;
    }
    el.innerHTML = checked.map(c => '<span class="qsum-chip">' + c.dataset.label + '</span>').join("");
  }

  function syncSelect(id) {
    const sel = document.getElementById(id);
    if (!sel) return;
    setText(id, sel.value || "");
  }

  function syncMaintenance() {
    const cb = form.querySelector('input[name="maintenance"]:checked');
    setText("maintenance", cb ? "Incluse" : "");
  }

  // wire up
  ["type", "formule", "paiement"].forEach(n => {
    form.querySelectorAll('input[name="' + n + '"]').forEach(r =>
      r.addEventListener("change", () => syncRadios(n)));
  });
  form.querySelectorAll('input[name="modules"]').forEach(c =>
    c.addEventListener("change", syncModules));
  form.querySelectorAll('input[name="maintenance"]').forEach(c =>
    c.addEventListener("change", syncMaintenance));
  ["budget", "delai"].forEach(id => {
    const sel = document.getElementById(id);
    if (sel) sel.addEventListener("change", () => syncSelect(id));
  });
  const domaineEl = document.getElementById("domaine");
  if (domaineEl) domaineEl.addEventListener("input", () => syncSelect("domaine"));

  /* ---------- persistence (survive reloads during review) ---------- */
  const STORE = "sws-quote-draft";
  function save() {
    try {
      const data = new FormData(form);
      const obj = {};
      for (const [k, v] of data.entries()) {
        if (obj[k]) { obj[k] = [].concat(obj[k], v); } else { obj[k] = v; }
      }
      localStorage.setItem(STORE, JSON.stringify(obj));
    } catch (e) {}
  }
  function restore() {
    let obj;
    try { obj = JSON.parse(localStorage.getItem(STORE) || "null"); } catch (e) {}
    if (!obj) return;
    Object.keys(obj).forEach(k => {
      const vals = [].concat(obj[k]);
      form.querySelectorAll('[name="' + k + '"]').forEach(el => {
        if (el.type === "radio" || el.type === "checkbox") {
          if (vals.includes(el.value)) el.checked = true;
        } else {
          el.value = vals[0] || "";
        }
      });
    });
    // refresh summary
    ["type", "formule", "paiement"].forEach(syncRadios);
    syncModules();
    syncMaintenance();
    ["budget", "delai", "domaine"].forEach(syncSelect);
  }
  form.addEventListener("input", save);
  form.addEventListener("change", save);
  restore();

  /* ---------- pre-select formule from URL (?formule=Professionnel) ---------- */
  try {
    const pf = new URLSearchParams(location.search).get("formule");
    if (pf) {
      const r = form.querySelector('input[name="formule"][value="' + pf + '"]');
      if (r) { r.checked = true; syncRadios("formule"); save(); }
    }
  } catch (e) {}

  /* ---------- submit ---------- */
  form.addEventListener("submit", e => {
    e.preventDefault();
    // light validation: require name + email
    const nom = form.querySelector("#nom");
    const email = form.querySelector("#email");
    let firstBad = null;
    [nom, email].forEach(f => {
      const bad = !f.value.trim() || (f.type === "email" && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.value));
      f.style.borderColor = bad ? "#e2724f" : "";
      if (bad && !firstBad) firstBad = f;
    });
    if (firstBad) {
      firstBad.focus();
      if (hasGSAP) gsap.fromTo(firstBad, { x: -6 }, { x: 0, duration: .4, ease: "elastic.out(1,0.4)" });
      return;
    }

    try { localStorage.removeItem(STORE); } catch (e) {}

    const done = document.getElementById("quoteDone");
    form.classList.add("sent");
    if (done) {
      done.classList.add("show");
      if (hasGSAP && !reduce) {
        gsap.from(done, { opacity: 0, y: 24, duration: .7, ease: "power3.out" });
        gsap.from(".qdone-ic", { scale: .4, opacity: 0, duration: .6, ease: "back.out(1.7)", delay: .1 });
      }
      const top = done.getBoundingClientRect().top + window.scrollY - 140;
      window.scrollTo({ top, behavior: reduce ? "auto" : "smooth" });
    }
  });
})();
