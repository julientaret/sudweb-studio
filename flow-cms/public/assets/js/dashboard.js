/**
 * Visionneuse galerie du tableau de bord.
 *
 * Externe (CSP `default-src 'self'`). Slider simple : une image visible à la fois,
 * flèches précédent/suivant, points de navigation et défilement automatique.
 * Inerte s'il n'y a aucune image (la section n'est alors pas rendue côté serveur).
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-gallery]');
    if (!root) { return; }

    var track = root.querySelector('.dg-track');
    var slides = root.querySelectorAll('.dg-slide');
    var dotsWrap = root.querySelector('[data-dots]');
    var n = slides.length;
    if (!track || n === 0) { return; }

    var idx = 0;
    var timer = null;
    var dots = [];

    // Points de navigation (un par image).
    for (var i = 0; i < n; i++) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'dg-dot';
        dot.setAttribute('aria-label', 'Image ' + (i + 1));
        (function (k) {
            dot.addEventListener('click', function () { go(k); });
        })(i);
        dotsWrap.appendChild(dot);
        dots.push(dot);
    }

    function render() {
        track.style.transform = 'translateX(' + (-idx * 100) + '%)';
        dots.forEach(function (d, k) { d.classList.toggle('active', k === idx); });
    }

    function go(k) {
        idx = (k + n) % n;
        render();
        restart();
    }

    function restart() {
        if (timer) { clearInterval(timer); }
        if (n > 1) {
            timer = setInterval(function () { go(idx + 1); }, 4500);
        }
    }

    var prev = root.querySelector('[data-prev]');
    var next = root.querySelector('[data-next]');
    if (prev) { prev.addEventListener('click', function () { go(idx - 1); }); }
    if (next) { next.addEventListener('click', function () { go(idx + 1); }); }

    render();
    restart();
})();

/**
 * Personnalisation de l'accès rapide.
 *
 * Mode édition (bouton « Personnaliser ») : on réordonne les cartes par
 * glisser-déposer, on les masque (croix) ou on les rajoute (panneau de pastilles).
 * Le tutoriel (cellule fixe) ne bouge pas et n'est pas masquable. L'agencement
 * est sauvegardé côté serveur (par compte) à chaque modification.
 */
(function () {
    'use strict';

    var grid = document.querySelector('[data-dash-grid]');
    var editBtn = document.querySelector('[data-dash-edit]');
    if (!grid || !editBtn) { return; }

    var addPanel = document.querySelector('[data-dash-add]');
    var addList = document.querySelector('[data-add-list]');
    var hiddenStock = document.querySelector('[data-hidden-cells]');
    var saveUrl = grid.getAttribute('data-save-url');
    var csrf = grid.getAttribute('data-csrf');
    var fixedCell = grid.querySelector('.dash-cell--fixed'); // tutoriel : reste dernier

    var stash = {};      // cellules masquées, indexées par clé (url)
    var editing = false;
    var dragged = null;

    function closest(el, sel) {
        return el && el.closest ? el.closest(sel) : null;
    }

    // Construit la pastille d'ajout correspondant à une cellule masquée.
    function chipFor(cell) {
        var key = cell.getAttribute('data-key');
        var strong = cell.querySelector('.dash-body strong');
        var ic = cell.querySelector('.dash-ic');

        var chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'dash-chip';
        chip.setAttribute('data-key', key);

        var icSpan = document.createElement('span');
        icSpan.className = 'dash-chip-ic';
        if (ic) { icSpan.innerHTML = ic.innerHTML; }

        var txt = document.createElement('span');
        txt.textContent = strong ? strong.textContent : key;

        var plus = document.createElement('span');
        plus.className = 'dash-chip-plus';
        plus.textContent = '+';

        chip.appendChild(icSpan);
        chip.appendChild(txt);
        chip.appendChild(plus);
        chip.addEventListener('click', function () { showCell(key); });
        return chip;
    }

    // Masque une cellule : on la sort de la grille vers le stash + pastille.
    function hideCell(cell, doSave) {
        var key = cell.getAttribute('data-key');
        if (cell.parentNode) { cell.parentNode.removeChild(cell); }
        cell.removeAttribute('draggable');
        stash[key] = cell;
        addList.appendChild(chipFor(cell));
        if (doSave) { save(); }
    }

    // Réaffiche une cellule masquée : retour dans la grille, avant le tutoriel.
    function showCell(key) {
        var cell = stash[key];
        if (!cell) { return; }
        delete stash[key];
        grid.insertBefore(cell, fixedCell);
        if (editing) { cell.setAttribute('draggable', 'true'); }
        var chip = addList.querySelector('.dash-chip[data-key="' + key + '"]');
        if (chip) { addList.removeChild(chip); }
        save();
    }

    function applyDraggable() {
        Array.prototype.forEach.call(grid.querySelectorAll('.dash-cell[data-key]'), function (c) {
            if (editing) { c.setAttribute('draggable', 'true'); }
            else { c.removeAttribute('draggable'); }
        });
    }

    function save() {
        if (!saveUrl) { return; }
        var order = [];
        Array.prototype.forEach.call(grid.querySelectorAll('.dash-cell[data-key]'), function (c) {
            order.push(c.getAttribute('data-key'));
        });
        var body = 'order=' + encodeURIComponent(JSON.stringify(order))
                 + '&hidden=' + encodeURIComponent(JSON.stringify(Object.keys(stash)))
                 + '&_csrf=' + encodeURIComponent(csrf || '');
        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body,
            credentials: 'same-origin'
        });
    }

    // Au chargement : transférer les cartes masquées (rendues côté serveur) vers le stash.
    if (hiddenStock) {
        Array.prototype.forEach.call(hiddenStock.querySelectorAll('.dash-cell[data-key]'), function (cell) {
            hideCell(cell, false);
        });
        if (hiddenStock.parentNode) { hiddenStock.parentNode.removeChild(hiddenStock); }
    }

    editBtn.addEventListener('click', function () {
        editing = !editing;
        grid.classList.toggle('editing', editing);
        editBtn.classList.toggle('is-active', editing);
        editBtn.textContent = editing ? 'Terminer' : 'Personnaliser';
        if (addPanel) { addPanel.hidden = !editing; }
        applyDraggable();
    });

    // Retirer une carte / neutraliser la navigation pendant l'édition.
    grid.addEventListener('click', function (e) {
        var btn = closest(e.target, '[data-remove]');
        if (btn) {
            e.preventDefault();
            var cell = closest(btn, '.dash-cell');
            if (cell) { hideCell(cell, true); }
            return;
        }
        if (editing && closest(e.target, 'a')) { e.preventDefault(); }
    });

    // Glisser-déposer (réordonnancement parmi les cartes mobiles).
    grid.addEventListener('dragstart', function (e) {
        var cell = closest(e.target, '.dash-cell[data-key]');
        if (!editing || !cell) { return; }
        dragged = cell;
        cell.classList.add('dragging');
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', cell.getAttribute('data-key')); } catch (_) {}
        }
    });

    grid.addEventListener('dragover', function (e) {
        if (!dragged) { return; }
        e.preventDefault();
        var over = closest(e.target, '.dash-cell[data-key]');
        if (!over || over === dragged) { return; }
        var rect = over.getBoundingClientRect();
        var after = (e.clientX - rect.left) > rect.width / 2;
        grid.insertBefore(dragged, after ? over.nextSibling : over);
        if (fixedCell) { grid.appendChild(fixedCell); } // le tutoriel reste en dernier
    });

    grid.addEventListener('dragend', function () {
        if (!dragged) { return; }
        dragged.classList.remove('dragging');
        dragged = null;
        save();
    });
})();
