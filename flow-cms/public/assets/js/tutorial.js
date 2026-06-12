/**
 * Onglets de la page tutoriel.
 *
 * Externe (CSP `default-src 'self'`). Au clic sur un onglet de gauche, affiche le
 * panneau correspondant à droite. Le premier onglet/panneau est marqué actif
 * côté serveur → la page reste lisible même si ce script ne se charge pas.
 */
(function () {
    'use strict';

    var nav = document.querySelector('[data-tuto-nav]');
    if (!nav) { return; }

    var tabs = nav.querySelectorAll('[data-tab]');
    var panels = document.querySelectorAll('[data-panel]');

    function show(id) {
        tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === id); });
        panels.forEach(function (p) { p.classList.toggle('active', p.dataset.panel === id); });
    }

    nav.addEventListener('click', function (e) {
        var tab = e.target.closest('[data-tab]');
        if (tab) { show(tab.dataset.tab); }
    });
})();
