/**
 * Éditeur des modules « collection » (Réalisations, Sliders) — un seul élément
 * par formulaire : titre, description rich text (Quill) et une photo média.
 *
 * Externe (CSP `default-src 'self'`). Découplé du page-builder
 * (pages-editor.js) : il n'attend ni #rows ni #layout, juste un ou plusieurs
 * champs `.quill-editor` et, optionnellement, la modale d'images partagée
 * (#media-modal) avec des champs `.image-field`. Mêmes conventions de données
 * (data-quill-delta / data-quill-input, .media-pick, .image-field[data-input])
 * que le page-builder, pour réutiliser la modale et les styles existants.
 */
(function () {
    'use strict';

    // --- Quill ------------------------------------------------------------
    function initQuills() {
        document.querySelectorAll('.quill-editor').forEach(function (el) {
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
        });
    }

    // --- Sélection d'image via la modale partagée -------------------------
    var mediaModal = document.getElementById('media-modal');
    var activeImageField = null;

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

    document.addEventListener('click', function (e) {
        var chooseImg = e.target.closest('.image-choose');
        if (chooseImg) { openMediaModal(chooseImg.closest('.image-field')); return; }
        var clearImg = e.target.closest('.image-clear');
        if (clearImg) { clearImage(clearImg.closest('.image-field')); }
    });

    initQuills();
})();
