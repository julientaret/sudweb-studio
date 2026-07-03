/**
 * Admin « Horaires d'ouverture » : décoche = grise les champs d'heure du jour,
 * et gère l'ajout/suppression de lignes de fermetures exceptionnelles via le
 * <template> #oh-tpl-exception (même mécanisme que pages-editor.js : clone du
 * template, remplacement du token __OKEY__ par une clé générée).
 */
(function () {
    'use strict';

    document.querySelectorAll('.oh-day-row').forEach(function (row) {
        var closed = row.querySelector('.oh-closed');
        var times = row.querySelectorAll('input[type=time]');
        if (!closed) {
            return;
        }
        closed.addEventListener('change', function () {
            times.forEach(function (input) {
                input.disabled = closed.checked;
            });
        });
    });

    var rowsEl = document.getElementById('oh-exceptions-rows');
    var addBtn = document.getElementById('oh-add-exception');
    if (!rowsEl || !addBtn) {
        return;
    }

    function uid() {
        return 'e' + Math.random().toString(36).slice(2, 8) + Date.now().toString(36).slice(-3);
    }

    function tplClone(id, token, value) {
        var tpl = document.getElementById(id);
        if (!tpl) {
            return null;
        }
        var html = tpl.innerHTML.split(token).join(value);
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        return holder.firstElementChild;
    }

    addBtn.addEventListener('click', function () {
        var row = tplClone('oh-tpl-exception', '__OKEY__', uid());
        if (row) {
            rowsEl.appendChild(row);
        }
    });

    rowsEl.addEventListener('click', function (e) {
        var remove = e.target.closest('.oh-exception-remove');
        if (remove) {
            remove.closest('.oh-exception-row').remove();
        }
    });
})();
