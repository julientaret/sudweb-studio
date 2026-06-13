/**
 * Éditeur de texte riche pour les pages légales (module LegalPages).
 *
 * Version minimale, monozone, de l'init Quill de pages-editor.js : une seule
 * `.quill-editor`, dont le Delta est sérialisé dans l'input caché à chaque frappe
 * et juste avant l'envoi du formulaire. On stocke le Delta (JSON), jamais du HTML ;
 * le rendu front passe par App\Modules\Pages\DeltaRenderer (borné).
 *
 * La barre d'outils et la PALETTE doivent rester alignées sur :
 *   - pages-editor.js (même expérience d'édition) ;
 *   - DeltaRenderer::ALLOWED_COLORS (sinon une couleur saisie est ignorée au rendu).
 */
(function () {
    'use strict';

    var el = document.querySelector('.quill-editor');
    if (!el || typeof Quill === 'undefined' || el.dataset.quillReady === '1') {
        return;
    }

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

    var form = el.closest('form');
    if (form) { form.addEventListener('submit', sync); }

    el.dataset.quillReady = '1';
})();
