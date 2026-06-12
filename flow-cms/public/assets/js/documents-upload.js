/**
 * Glisser-déposer multiple pour la bibliothèque de documents + copie du lien.
 *
 * Externe (pas d'inline) pour respecter la CSP `default-src 'self'`.
 *
 * Amélioration progressive : sans JS, la carte reste un formulaire classique
 * mono-fichier. Avec JS, elle devient une dropzone qui envoie chaque fichier en
 * AJAX (un XHR par document → barre de progression réelle et statut par fichier).
 * Les garde-fous de sécurité restent côté serveur ; la validation client (par
 * extension) n'est là que pour un retour immédiat.
 *
 * Bonus « réutilisable sur le site » : chaque carte a un bouton « Copier le lien »
 * qui place l'URL publique du document dans le presse-papiers, prête à coller dans
 * un lien de page.
 */
(function () {
    'use strict';

    // --- Copie du lien public (délégation, marche même sans dropzone) ---------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-copy-link]') : null;
        if (!btn) {
            return;
        }
        e.preventDefault();
        var url = btn.getAttribute('data-copy-link');
        var done = function () {
            var prev = btn.textContent;
            btn.classList.add('copied');
            btn.textContent = 'Lien copié ✓';
            setTimeout(function () {
                btn.classList.remove('copied');
                btn.textContent = prev;
            }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done, function () { fallbackCopy(url, done); });
        } else {
            fallbackCopy(url, done);
        }
    });

    function fallbackCopy(text, done) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'absolute';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (err) { /* ignore */ }
        document.body.removeChild(ta);
    }

    // --- Dropzone -------------------------------------------------------------
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
    var maxSize = parseInt(zone.dataset.maxSize, 10) || (20 * 1024 * 1024);
    var exts = (zone.dataset.exts || '').split(',').filter(Boolean);
    var token = csrf.value;

    var MAX_CONCURRENT = 3;
    var queue = [];      // fichiers en attente
    var active = 0;      // uploads en cours
    var errors = 0;      // échecs cumulés
    var succeeded = 0;   // succès cumulés

    zone.classList.add('js-dropzone');
    input.removeAttribute('required'); // plus de submit natif en mode JS

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

    function enqueue(fileList) {
        var files = Array.prototype.slice.call(fileList);
        if (files.length === 0) {
            return;
        }
        list.hidden = false;
        files.forEach(function (file) {
            var row = makeRow(file.name);
            if (!isAllowed(file.name)) {
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
    // cartes (avec leurs formulaires libellé / déplacer / supprimer). Sinon on
    // laisse les erreurs visibles et on propose un lien vers la liste.
    function done() {
        if (errors === 0 && succeeded > 0) {
            window.location.assign(reloadUrl);
            return;
        }
        if (succeeded > 0) {
            showReloadNotice();
        }
    }

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
        link.textContent = succeeded + ' document' + (succeeded > 1 ? 's' : '') + ' ajouté' + (succeeded > 1 ? 's' : '') + ' — actualiser la liste';
        li.appendChild(link);
        list.appendChild(li);
    }

    function isAllowed(name) {
        var dot = name.lastIndexOf('.');
        if (dot < 0) {
            return false;
        }
        var ext = name.slice(dot + 1).toLowerCase();
        return exts.length === 0 || exts.indexOf(ext) !== -1;
    }
})();
