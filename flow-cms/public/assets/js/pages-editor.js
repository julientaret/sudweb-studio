/**
 * Éditeur de blocs des Pages, en lignes & colonnes (brief §4 & §8).
 *
 * Externe (CSP `default-src 'self'`). Gère : lignes réordonnables, 1 à 4 colonnes
 * par ligne, ajout/suppression de blocs par colonne, glisser-déposer des blocs
 * entre colonnes, l'édition richtext via Quill et la sélection d'image via une
 * modale de vignettes partagée. À la sauvegarde, la structure
 * (lignes → colonnes → clés de blocs) est sérialisée dans le champ caché #layout ;
 * le serveur reconstruit le JSON en passant chaque bloc par sanitize().
 */
(function () {
    'use strict';

    var rowsEl = document.getElementById('rows');
    var form = document.getElementById('page-form');
    var layoutInput = document.getElementById('layout');
    if (!rowsEl || !form || !layoutInput) {
        return;
    }

    var quills = []; // { quill, hidden }

    function uid(prefix) {
        return prefix + Math.random().toString(36).slice(2, 8) + Date.now().toString(36).slice(-3);
    }

    function tplClone(id, token, value) {
        var tpl = document.getElementById(id);
        if (!tpl) { return null; }
        var html = tpl.innerHTML;
        if (token) { html = html.split(token).join(value); }
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        return holder.firstElementChild;
    }

    // --- Sérialisation de la structure -----------------------------------
    function updateLayout() {
        var rows = [];
        rowsEl.querySelectorAll(':scope > .row').forEach(function (row) {
            var columns = [];
            row.querySelectorAll(':scope > .row-cols > .col').forEach(function (col) {
                var keys = [];
                col.querySelectorAll(':scope > .col-blocks > .block-card').forEach(function (card) {
                    keys.push(card.dataset.key);
                });
                columns.push(keys);
            });
            rows.push({
                key: row.dataset.key,
                cols: parseInt(row.dataset.cols, 10) || 1,
                columns: columns
            });
        });
        layoutInput.value = JSON.stringify(rows);
    }

    // --- Quill ------------------------------------------------------------
    function initQuills(scope) {
        (scope || document).querySelectorAll('.quill-editor').forEach(function (el) {
            if (el.dataset.quillReady === '1' || typeof Quill === 'undefined') { return; }
            var palette = [
                '#1d4ed8', '#dc2626', '#16a34a', '#ea580c',
                '#ca8a04', '#9333ea', '#0891b2', '#374151'
            ];
            var quill = new Quill(el, {
                theme: 'snow',
                formats: [
                    'bold', 'italic', 'underline', 'strike',
                    'color', 'link', 'header', 'list',
                    'align', 'blockquote', 'code-block'
                ],
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: palette }],
                        [{ header: 2 }, { header: 3 }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: '' }, { align: 'center' }, { align: 'right' }, { align: 'justify' }],
                        ['blockquote', 'code-block'],
                        ['link'],
                        ['clean']
                    ]
                }
            });
            var initial = { ops: [] };
            try { initial = JSON.parse(el.dataset.quillDelta || '{"ops":[]}'); } catch (e) {}
            quill.setContents(initial);
            var hidden = document.getElementById(el.dataset.quillInput);
            function sync() { if (hidden) { hidden.value = JSON.stringify(quill.getContents()); } }
            quill.on('text-change', sync);
            sync();
            el.dataset.quillReady = '1';
            quills.push({ quill: quill, hidden: hidden });
        });
    }

    // --- Sélection d'image via la modale partagée -------------------------
    // Un seul #media-modal pour tout l'éditeur ; on mémorise le champ actif et
    // on y réinjecte l'id + l'aperçu au clic sur une vignette. Construction DOM
    // par createElement/textContent (jamais d'innerHTML) : pas d'évasion possible.
    var mediaModal = document.getElementById('media-modal');
    var activeImageField = null;

    // Filtre par dossier : montre/cache les vignettes selon le dossier choisi.
    // 'all' = tout ; 'none' = images sans dossier ; sinon l'id du dossier.
    function applyMediaFilter(folder) {
        var btns = mediaModal.querySelectorAll('.media-filter');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.toggle('active', btns[i].dataset.folder === folder);
        }
        var picks = mediaModal.querySelectorAll('.media-pick');
        for (var j = 0; j < picks.length; j++) {
            var f = picks[j].dataset.folder || 'none';
            picks[j].hidden = !(folder === 'all' || folder === f);
        }
    }

    function openMediaModal(field) {
        if (!mediaModal) { return; }
        activeImageField = field;
        // Repart sur « Tout » à chaque ouverture (s'il y a un filtre).
        if (mediaModal.querySelector('.media-filter')) { applyMediaFilter('all'); }
        mediaModal.hidden = false;
    }
    function closeMediaModal() {
        if (!mediaModal) { return; }
        mediaModal.hidden = true;
        activeImageField = null;
    }
    function setImage(field, id, thumb) {
        var input = document.getElementById(field.dataset.input);
        if (input) { input.value = id; }
        var preview = field.querySelector('.image-preview');
        if (preview) {
            preview.classList.remove('empty');
            preview.textContent = '';
            var img = document.createElement('img');
            img.src = thumb;
            img.alt = '';
            preview.appendChild(img);
        }
        var clear = field.querySelector('.image-clear');
        if (clear) { clear.hidden = false; }
    }
    function clearImage(field) {
        var input = document.getElementById(field.dataset.input);
        if (input) { input.value = ''; }
        var preview = field.querySelector('.image-preview');
        if (preview) {
            preview.classList.add('empty');
            preview.textContent = '';
            var span = document.createElement('span');
            span.className = 'hint';
            span.textContent = 'Aucune image';
            preview.appendChild(span);
        }
        var clear = field.querySelector('.image-clear');
        if (clear) { clear.hidden = true; }
    }

    if (mediaModal) {
        mediaModal.addEventListener('click', function (e) {
            if (e.target.closest('[data-close]')) { closeMediaModal(); return; }
            var filter = e.target.closest('.media-filter');
            if (filter) { applyMediaFilter(filter.dataset.folder); return; }
            var pick = e.target.closest('.media-pick');
            if (pick && activeImageField) {
                setImage(activeImageField, pick.dataset.id, pick.dataset.thumb);
                closeMediaModal();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !mediaModal.hidden) { closeMediaModal(); }
        });
    }

    // Champs image au niveau document : fonctionne pour les blocs image (dans
    // #rows) comme pour un champ hors blocs (ex. vignette d'un événement Agenda).
    document.addEventListener('click', function (e) {
        var chooseImg = e.target.closest('.image-choose');
        if (chooseImg) { openMediaModal(chooseImg.closest('.image-field')); return; }
        var clearImg = e.target.closest('.image-clear');
        if (clearImg) { clearImage(clearImg.closest('.image-field')); }
    });

    // --- Colonnes (1 à 4) -------------------------------------------------
    function setColumns(row, n) {
        n = Math.max(1, Math.min(4, n));
        var cols = row.querySelectorAll(':scope > .row-cols > .col');
        var wrap = row.querySelector(':scope > .row-cols');
        var current = cols.length;

        if (n > current) {
            for (var i = current; i < n; i++) {
                wrap.appendChild(tplClone('tpl-col'));
            }
        } else if (n < current) {
            // On ne perd pas de contenu : les blocs des colonnes retirées sont
            // déplacés dans la dernière colonne conservée.
            var keep = cols[n - 1].querySelector(':scope > .col-blocks');
            for (var j = n; j < current; j++) {
                var blocks = cols[j].querySelectorAll(':scope > .col-blocks > .block-card');
                blocks.forEach(function (b) { keep.appendChild(b); });
                cols[j].remove();
            }
        }

        row.dataset.cols = n;
        row.querySelectorAll(':scope > .row-head .col-count').forEach(function (btn) {
            btn.classList.toggle('active', parseInt(btn.dataset.cols, 10) === n);
        });
        updateLayout();
    }

    // --- Délégation des clics --------------------------------------------
    document.getElementById('add-row').addEventListener('click', function () {
        var row = tplClone('tpl-row', '__RKEY__', uid('r'));
        rowsEl.appendChild(row);
        updateLayout();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    rowsEl.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-block');
        if (addBtn) {
            var colBlocks = addBtn.closest('.col').querySelector(':scope > .col-blocks');
            var card = tplClone('tpl-' + addBtn.dataset.type, '__KEY__', uid('b'));
            colBlocks.appendChild(card);
            initQuills(card);
            updateLayout();
            return;
        }
        var countBtn = e.target.closest('.col-count');
        if (countBtn) {
            setColumns(countBtn.closest('.row'), parseInt(countBtn.dataset.cols, 10));
            return;
        }
        var removeBlock = e.target.closest('.block-remove');
        if (removeBlock) {
            removeBlock.closest('.block-card').remove();
            updateLayout();
            return;
        }
        var removeRow = e.target.closest('.row-remove');
        if (removeRow) {
            if (rowsEl.querySelectorAll(':scope > .row').length > 1 || confirm('Supprimer cette ligne et son contenu ?')) {
                removeRow.closest('.row').remove();
                updateLayout();
            }
        }
    });

    // --- Glisser-déposer (blocs entre colonnes, et lignes) ---------------
    var dragMode = null;   // 'block' | 'row'
    var dragSrc = null;

    rowsEl.addEventListener('mousedown', function (e) {
        var card = e.target.closest('.block-card');
        var row = e.target.closest('.row');
        if (e.target.closest('.drag-handle') && card) {
            card.draggable = true; dragMode = 'block';
        } else if (e.target.closest('.row-handle') && row) {
            row.draggable = true; dragMode = 'row';
        } else {
            if (card) { card.draggable = false; }
            if (row) { row.draggable = false; }
        }
    });

    rowsEl.addEventListener('dragstart', function (e) {
        if (dragMode === 'block') {
            dragSrc = e.target.closest('.block-card');
        } else if (dragMode === 'row') {
            dragSrc = e.target.closest('.row');
        }
        if (dragSrc) { dragSrc.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });

    rowsEl.addEventListener('dragover', function (e) {
        if (!dragSrc) { return; }
        e.preventDefault();

        if (dragMode === 'row') {
            var overRow = e.target.closest('.row');
            if (!overRow || overRow === dragSrc) { return; }
            var r = overRow.getBoundingClientRect();
            var afterR = (e.clientY - r.top) / r.height > 0.5;
            rowsEl.insertBefore(dragSrc, afterR ? overRow.nextSibling : overRow);
            return;
        }

        // block : insère dans la colonne survolée, à la bonne position.
        var col = e.target.closest('.col');
        if (!col) { return; }
        var list = col.querySelector(':scope > .col-blocks');
        document.querySelectorAll('.col.drag-over').forEach(function (c) { c.classList.remove('drag-over'); });
        col.classList.add('drag-over');

        var overCard = e.target.closest('.block-card');
        if (!overCard || overCard === dragSrc) {
            if (!list.contains(dragSrc)) { list.appendChild(dragSrc); }
            return;
        }
        var b = overCard.getBoundingClientRect();
        var afterB = (e.clientY - b.top) / b.height > 0.5;
        list.insertBefore(dragSrc, afterB ? overCard.nextSibling : overCard);
    });

    rowsEl.addEventListener('dragend', function () {
        if (dragSrc) { dragSrc.classList.remove('dragging'); dragSrc.draggable = false; }
        document.querySelectorAll('.col.drag-over').forEach(function (c) { c.classList.remove('drag-over'); });
        dragSrc = null; dragMode = null;
        updateLayout();
    });

    // --- Sauvegarde -------------------------------------------------------
    form.addEventListener('submit', function () {
        quills.forEach(function (q) { if (q.hidden) { q.hidden.value = JSON.stringify(q.quill.getContents()); } });
        updateLayout();
    });

    // Init.
    initQuills(document);
    updateLayout();
})();
