/**
 * Constructeur du formulaire de devis (admin).
 *
 * Externe (CSP `default-src 'self'`). Rend une interface d'édition des étapes et
 * questions du pop-up à partir du schéma JSON injecté dans #devis-schema, puis
 * resynchronise ce champ caché avant l'envoi du formulaire.
 *
 * Modèle en mémoire (steps) → rendu DOM. À chaque modification de structure
 * (ajout/suppression/déplacement), on relit le DOM dans le modèle puis on
 * re-rend, pour ne jamais perdre une saisie en cours.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-devis-builder]');
    if (!form) { return; }

    var input = document.getElementById('devis-schema');
    var root = form.querySelector('[data-builder-root]');
    if (!input || !root) { return; }

    var TYPES = (input.dataset.types || 'text,email,tel,textarea,select').split(',');
    var TYPE_LABELS = {
        text: 'Texte court', email: 'E-mail', tel: 'Téléphone',
        textarea: 'Zone de texte', select: 'Liste de choix'
    };

    var model = parse(input.value);

    function parse(value) {
        try {
            var data = JSON.parse(value);
            if (data && Array.isArray(data.steps)) { return data.steps; }
        } catch (e) { /* schéma illisible : on repart d'une étape vide */ }
        return [{ title: 'Étape 1', fields: [] }];
    }

    // --- Lecture du DOM vers le modèle -------------------------------------
    function syncFromDom() {
        var steps = [];
        root.querySelectorAll('[data-step]').forEach(function (stepEl) {
            var fields = [];
            stepEl.querySelectorAll('[data-field]').forEach(function (fEl) {
                var type = fEl.querySelector('[data-f-type]').value;
                var opts = [];
                var optsEl = fEl.querySelector('[data-f-options]');
                if (type === 'select' && optsEl) {
                    optsEl.value.split('\n').forEach(function (line) {
                        var v = line.trim();
                        if (v) { opts.push(v); }
                    });
                }
                fields.push({
                    key: fEl.dataset.key || '',
                    label: fEl.querySelector('[data-f-label]').value.trim(),
                    type: type,
                    required: fEl.querySelector('[data-f-required]').checked,
                    options: opts,
                    minlength: parseInt(fEl.dataset.minlength || '0', 10) || 0,
                    core: fEl.dataset.core === '1'
                });
            });
            steps.push({ title: stepEl.querySelector('[data-step-title]').value.trim(), fields: fields });
        });
        model = steps;
    }

    // --- Rendu --------------------------------------------------------------
    function el(tag, attrs, html) {
        var n = document.createElement(tag);
        if (attrs) { Object.keys(attrs).forEach(function (k) { n.setAttribute(k, attrs[k]); }); }
        if (html != null) { n.innerHTML = html; }
        return n;
    }

    function btn(label, cls) {
        var b = el('button', { type: 'button', 'class': 'db-mini ' + (cls || '') });
        b.textContent = label;
        return b;
    }

    function render() {
        root.innerHTML = '';

        model.forEach(function (step, si) {
            var stepEl = el('div', { 'class': 'db-step', 'data-step': si });

            // En-tête d'étape : numéro, titre, outils.
            var head = el('div', { 'class': 'db-step-head' });
            head.appendChild(el('span', { 'class': 'db-num' }, String(si + 1)));

            var titleInput = el('input', { type: 'text', 'data-step-title': '', placeholder: 'Titre de l’étape' });
            titleInput.value = step.title || '';
            head.appendChild(titleInput);

            var tools = el('div', { 'class': 'db-tools' });
            var up = btn('↑'); up.disabled = si === 0;
            up.addEventListener('click', function () { syncFromDom(); move(model, si, si - 1); render(); });
            var down = btn('↓'); down.disabled = si === model.length - 1;
            down.addEventListener('click', function () { syncFromDom(); move(model, si, si + 1); render(); });
            var del = btn('Supprimer l’étape', 'db-danger'); del.disabled = model.length <= 1;
            del.addEventListener('click', function () {
                syncFromDom(); model.splice(si, 1); render();
            });
            tools.appendChild(up); tools.appendChild(down); tools.appendChild(del);
            head.appendChild(tools);
            stepEl.appendChild(head);

            // Questions.
            (step.fields || []).forEach(function (field, fi) {
                stepEl.appendChild(renderField(step, field, si, fi));
            });

            var addField = btn('+ Ajouter une question', 'db-add-field');
            addField.addEventListener('click', function () {
                syncFromDom();
                model[si].fields.push({ key: '', label: '', type: 'text', required: false, options: [], minlength: 0, core: false });
                render();
            });
            stepEl.appendChild(addField);

            root.appendChild(stepEl);
        });

        var addStep = btn('+ Ajouter une étape', 'db-add-step');
        addStep.addEventListener('click', function () {
            syncFromDom();
            model.push({ title: 'Étape ' + (model.length + 1), fields: [] });
            render();
        });
        root.appendChild(addStep);
    }

    function renderField(step, field, si, fi) {
        var core = !!field.core;
        var fEl = el('div', { 'class': 'db-field', 'data-field': '' });
        fEl.dataset.key = field.key || '';
        fEl.dataset.core = core ? '1' : '0';
        fEl.dataset.minlength = String(field.minlength || 0);

        var row = el('div', { 'class': 'db-field-row' });

        // Libellé.
        var labelCell = el('div');
        labelCell.appendChild(el('label', null, 'Libellé' + (core ? '<span class="db-core-tag">obligatoire</span>' : '')));
        var labelInput = el('input', { type: 'text', 'data-f-label': '', placeholder: 'Ex. Type de projet' });
        labelInput.value = field.label || '';
        labelCell.appendChild(labelInput);
        row.appendChild(labelCell);

        // Type.
        var typeCell = el('div');
        typeCell.appendChild(el('label', null, 'Type'));
        var typeSel = el('select', { 'data-f-type': '' });
        TYPES.forEach(function (t) {
            var o = el('option', { value: t }, TYPE_LABELS[t] || t);
            if (t === field.type) { o.setAttribute('selected', 'selected'); }
            typeSel.appendChild(o);
        });
        if (core) { typeSel.disabled = true; }
        typeSel.addEventListener('change', function () { syncFromDom(); render(); });
        typeCell.appendChild(typeSel);
        row.appendChild(typeCell);

        // Obligatoire.
        var reqCell = el('div');
        reqCell.appendChild(el('label', null, 'Réponse'));
        var reqWrap = el('span', { 'class': 'db-req' });
        var req = el('input', { type: 'checkbox', 'data-f-required': '' });
        if (field.required) { req.checked = true; }
        if (core) { req.disabled = true; req.checked = true; }
        reqWrap.appendChild(req);
        reqWrap.appendChild(document.createTextNode('obligatoire'));
        reqCell.appendChild(reqWrap);
        row.appendChild(reqCell);

        // Suppression.
        var delCell = el('div');
        var del = btn('Retirer', 'db-danger');
        if (core) { del.disabled = true; }
        del.addEventListener('click', function () {
            syncFromDom(); model[si].fields.splice(fi, 1); render();
        });
        delCell.appendChild(del);
        row.appendChild(delCell);

        fEl.appendChild(row);

        // Options (liste de choix).
        if (field.type === 'select') {
            var opts = el('div', { 'class': 'db-opts' });
            opts.appendChild(el('label', null, 'Choix proposés (un par ligne)'));
            var ta = el('textarea', { 'data-f-options': '', rows: '3', placeholder: 'Option 1\nOption 2' });
            ta.value = (field.options || []).join('\n');
            opts.appendChild(ta);
            fEl.appendChild(opts);
        }

        return fEl;
    }

    function move(arr, from, to) {
        if (to < 0 || to >= arr.length) { return; }
        var item = arr.splice(from, 1)[0];
        arr.splice(to, 0, item);
    }

    // --- Envoi --------------------------------------------------------------
    form.addEventListener('submit', function () {
        syncFromDom();
        input.value = JSON.stringify({ steps: model });
    });

    render();
})();
