/**
 * Glisser-déposer multiple pour la bibliothèque d'images (brief §5).
 *
 * Externe (pas d'inline) pour respecter la CSP `default-src 'self'`.
 *
 * Amélioration progressive : sans JS, la carte reste un formulaire classique
 * mono-fichier. Avec JS, elle devient une dropzone qui envoie chaque fichier
 * en AJAX (un XHR par image → barre de progression réelle et statut par image).
 * Les garde-fous de sécurité restent côté serveur ; la validation client n'est
 * là que pour un retour immédiat.
 */
(function () {
    'use strict';

    var zone = document.getElementById('dropzone');
    if (!zone) {
        return;
    }

    var input = zone.querySelector('input[type=file]');
    var list = document.getElementById('upload-progress');
    var form = document.getElementById('upload-form');
    var csrf = form ? form.querySelector('input[name=_csrf]') : null;
    if (!input || !list || !csrf) {
        return;
    }

    var uploadUrl = zone.dataset.uploadUrl;
    var reloadUrl = zone.dataset.reloadUrl || window.location.href;
    var folder = zone.dataset.folder || '';
    var maxSize = parseInt(zone.dataset.maxSize, 10) || (5 * 1024 * 1024);
    var token = csrf.value;

    var MAX_CONCURRENT = 3;
    var queue = [];      // fichiers en attente
    var active = 0;      // uploads en cours
    var errors = 0;      // échecs cumulés
    var succeeded = 0;   // succès cumulés

    zone.classList.add('js-dropzone');
    input.removeAttribute('required'); // plus de submit natif en mode JS

    // --- Sélection des fichiers -------------------------------------------

    // Clic sur la zone → ouvre le sélecteur (sauf clic direct sur l'input).
    zone.addEventListener('click', function (e) {
        if (e.target !== input) {
            input.click();
        }
    });

    input.addEventListener('change', function () {
        enqueue(input.files);
        input.value = ''; // permet de re-sélectionner les mêmes fichiers
    });

    // Glisser-déposer.
    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
    });
    ['dragleave', 'dragend', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            if (evt !== 'drop' || !zone.contains(e.relatedTarget)) {
                zone.classList.remove('dragover');
            }
        });
    });
    zone.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files) {
            enqueue(e.dataTransfer.files);
        }
    });

    // --- File d'attente ----------------------------------------------------

    function enqueue(fileList) {
        var files = Array.prototype.slice.call(fileList);
        if (files.length === 0) {
            return;
        }
        list.hidden = false;
        files.forEach(function (file) {
            var row = makeRow(file.name);
            if (!isImage(file)) {
                fail(row, 'Type non autorisé');
                return;
            }
            if (file.size > maxSize) {
                fail(row, 'Trop volumineux');
                return;
            }
            queue.push({ file: file, row: row });
        });
        pump();
    }

    function pump() {
        while (active < MAX_CONCURRENT && queue.length > 0) {
            var job = queue.shift();
            active++;
            send(job.file, job.row);
        }
    }

    // --- Envoi d'un fichier ------------------------------------------------

    function send(file, row) {
        var bar = row.querySelector('.up-bar > i');
        var status = row.querySelector('.up-status');

        var data = new FormData();
        data.append('_csrf', token);
        data.append('folder', folder);
        data.append('file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 100);
                bar.style.width = pct + '%';
                status.textContent = pct + '%';
            }
        });

        xhr.addEventListener('load', function () {
            var res = null;
            try {
                res = JSON.parse(xhr.responseText);
            } catch (err) { /* réponse non-JSON */ }

            if (xhr.status >= 200 && xhr.status < 300 && res && res.ok) {
                succeeded++;
                row.classList.add('done');
                status.textContent = 'OK';
            } else {
                fail(row, (res && res.error) ? res.error : 'Échec (' + xhr.status + ')');
            }
            finish();
        });

        xhr.addEventListener('error', function () {
            fail(row, 'Erreur réseau');
            finish();
        });

        xhr.send(data);
    }

    function finish() {
        active--;
        if (queue.length > 0) {
            pump();
            return;
        }
        if (active === 0) {
            done();
        }
    }

    // Tout est terminé. Si aucune erreur, on recharge pour afficher les vraies
    // cartes (avec leurs formulaires alt/déplacer/recadrer). Sinon on laisse les
    // erreurs visibles et on propose un lien vers les images ajoutées.
    function done() {
        if (errors === 0 && succeeded > 0) {
            window.location.assign(reloadUrl);
            return;
        }
        if (succeeded > 0) {
            showReloadNotice();
        }
    }

    // --- Rendu d'une ligne -------------------------------------------------

    function makeRow(name) {
        var li = document.createElement('li');

        var label = document.createElement('span');
        label.className = 'up-name';
        label.textContent = name;

        var bar = document.createElement('span');
        bar.className = 'up-bar';
        bar.appendChild(document.createElement('i'));

        var status = document.createElement('span');
        status.className = 'up-status';
        status.textContent = '0%';

        li.appendChild(label);
        li.appendChild(bar);
        li.appendChild(status);
        list.appendChild(li);
        return li;
    }

    function fail(row, message) {
        errors++;
        row.classList.remove('done');
        row.classList.add('error');
        var status = row.querySelector('.up-status');
        status.textContent = message;
    }

    function showReloadNotice() {
        var li = document.createElement('li');
        var link = document.createElement('a');
        link.href = reloadUrl;
        link.textContent = succeeded + ' image' + (succeeded > 1 ? 's' : '') + ' ajoutée' + (succeeded > 1 ? 's' : '') + ' — actualiser la liste';
        li.appendChild(link);
        list.appendChild(li);
    }

    function isImage(file) {
        return /^image\/(jpeg|png|webp|gif)$/.test(file.type);
    }
})();
