/* Sud Web Studio — interactions */
(function () {
  "use strict";
  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---- sticky header state ---- */
  var header = document.querySelector(".site-header");
  function onScroll() {
    if (window.scrollY > 12) header.classList.add("scrolled");
    else header.classList.remove("scrolled");
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* ---- reveal on scroll ---- */
  var reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window && !prefersReduced) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
    reveals.forEach(function (el) { io.observe(el); });
    // failsafe: reveal anything still hidden after load settles
    window.addEventListener("load", function () {
      setTimeout(function () {
        reveals.forEach(function (el) {
          var r = el.getBoundingClientRect();
          if (r.top < window.innerHeight && !el.classList.contains("in")) el.classList.add("in");
        });
      }, 1200);
    });
  } else {
    reveals.forEach(function (el) { el.classList.add("in"); });
  }

  /* ---- stats count up ---- */
  function animateCount(el) {
    var target = parseFloat(el.getAttribute("data-to"));
    var dur = 1400, start = null;
    var decimals = (el.getAttribute("data-dec") === "1") ? 1 : 0;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var val = target * eased;
      el.textContent = decimals ? val.toFixed(1) : Math.round(val).toLocaleString("fr-FR");
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = decimals ? target.toFixed(1) : target.toLocaleString("fr-FR");
    }
    requestAnimationFrame(step);
  }
  var nums = document.querySelectorAll("[data-to]");
  if ("IntersectionObserver" in window && !prefersReduced) {
    var io2 = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { animateCount(e.target); io2.unobserve(e.target); }
      });
    }, { threshold: 0.6 });
    nums.forEach(function (el) { io2.observe(el); });
  } else {
    nums.forEach(function (el) {
      var t = parseFloat(el.getAttribute("data-to"));
      el.textContent = (el.getAttribute("data-dec") === "1") ? t.toFixed(1) : t.toLocaleString("fr-FR");
    });
  }

  /* ---- magnetic buttons ---- */
  if (!prefersReduced && window.matchMedia("(pointer:fine)").matches) {
    document.querySelectorAll("[data-magnetic]").forEach(function (btn) {
      btn.addEventListener("mousemove", function (e) {
        var r = btn.getBoundingClientRect();
        var x = e.clientX - r.left - r.width / 2;
        var y = e.clientY - r.top - r.height / 2;
        btn.style.transform = "translate(" + x * 0.25 + "px," + y * 0.32 + "px)";
      });
      btn.addEventListener("mouseleave", function () { btn.style.transform = ""; });
    });
  }

  /* ---- hero collage subtle parallax ---- */
  if (!prefersReduced && window.matchMedia("(pointer:fine)").matches) {
    var collage = document.querySelector(".collage");
    if (collage) {
      var phs = collage.querySelectorAll(".ph");
      collage.addEventListener("mousemove", function (e) {
        var r = collage.getBoundingClientRect();
        var cx = (e.clientX - r.left) / r.width - 0.5;
        var cy = (e.clientY - r.top) / r.height - 0.5;
        phs.forEach(function (ph, i) {
          var depth = (i % 4 + 1) * 4;
          ph.style.transform = "translate(" + cx * depth + "px," + cy * depth + "px)";
        });
      });
      collage.addEventListener("mouseleave", function () {
        phs.forEach(function (ph) { ph.style.transform = ""; });
      });
    }
  }

  /* ---- mobile nav (simple jump) ---- */
  var toggle = document.querySelector(".nav-toggle");
  if (toggle) {
    toggle.addEventListener("click", function () {
      var t = document.querySelector("#contact");
      if (t) t.scrollIntoView({ behavior: prefersReduced ? "auto" : "smooth" });
    });
  }

  /* ---- réalisations carousel ---- */
  (function () {
    var track = document.querySelector("[data-work-track]");
    if (!track) return;
    var slides = track.querySelectorAll(".work-slide");
    var prevBtn = document.querySelector("[data-work-prev]");
    var nextBtn = document.querySelector("[data-work-next]");
    var bar = document.querySelector("[data-work-bar]");
    var curEl = document.querySelector("[data-work-cur]");
    var totalEl = document.querySelector("[data-work-total]");
    var total = slides.length;
    if (totalEl) totalEl.textContent = ("0" + total).slice(-2);

    function step() {
      return slides[0].getBoundingClientRect().width +
             parseFloat(getComputedStyle(track).columnGap || 0);
    }
    function index() {
      return Math.round(track.scrollLeft / step());
    }
    function update() {
      var i = Math.max(0, Math.min(total - 1, index()));
      if (curEl) curEl.textContent = ("0" + (i + 1)).slice(-2);
      if (bar) bar.style.transform = "scaleX(" + ((i + 1) / total) + ")";
      if (prevBtn) prevBtn.disabled = i <= 0;
      if (nextBtn) nextBtn.disabled = i >= total - 1;
    }
    function go(i) {
      i = Math.max(0, Math.min(total - 1, i));
      track.scrollTo({ left: i * step(), behavior: prefersReduced ? "auto" : "smooth" });
    }
    bar.style.transform = "scaleX(" + (1 / total) + ")";

    if (prevBtn) prevBtn.addEventListener("click", function () { go(index() - 1); });
    if (nextBtn) nextBtn.addEventListener("click", function () { go(index() + 1); });

    var raf;
    track.addEventListener("scroll", function () {
      update();
      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(update);
    }, { passive: true });

    track.addEventListener("keydown", function (e) {
      if (e.key === "ArrowRight") { e.preventDefault(); go(index() + 1); }
      else if (e.key === "ArrowLeft") { e.preventDefault(); go(index() - 1); }
    });

    /* drag to scroll (pointer-fine) */
    if (window.matchMedia("(pointer:fine)").matches) {
      var down = false, startX = 0, startScroll = 0, moved = 0;
      track.addEventListener("pointerdown", function (e) {
        down = true; moved = 0;
        startX = e.clientX; startScroll = track.scrollLeft;
        track.classList.add("dragging");
      });
      window.addEventListener("pointermove", function (e) {
        if (!down) return;
        var dx = e.clientX - startX;
        moved = Math.max(moved, Math.abs(dx));
        track.scrollLeft = startScroll - dx;
      });
      window.addEventListener("pointerup", function () {
        if (!down) return;
        down = false;
        track.classList.remove("dragging");
        go(index()); /* snap to nearest */
      });
    }

    window.addEventListener("resize", update);
    update();
  })();

  /* ---- year ---- */
  var y = document.querySelector("[data-year]");
  if (y) y.textContent = new Date().getFullYear();
})();
