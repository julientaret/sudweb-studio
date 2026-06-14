/**
 * Initialisation du recadrage Cropper.js (brief §5).
 *
 * Externe (pas d'inline) pour respecter la CSP `default-src 'self'`. Le front
 * renvoie les coordonnées x, y, w, h EN PIXELS DE L'ORIGINAL ; GD découpe côté
 * serveur et régénère les déclinaisons.
 */
(function () {
    'use strict';

    var image = document.getElementById('cropper-image');
    if (!image || typeof Cropper === 'undefined') {
        return;
    }

    var inputX = document.getElementById('crop-x');
    var inputY = document.getElementById('crop-y');
    var inputW = document.getElementById('crop-w');
    var inputH = document.getElementById('crop-h');

    // Recadrage existant à pré-sélectionner (data-* posés par la vue).
    var initial = null;
    if (image.dataset.cropW && image.dataset.cropH) {
        initial = {
            x: parseInt(image.dataset.cropX, 10) || 0,
            y: parseInt(image.dataset.cropY, 10) || 0,
            width: parseInt(image.dataset.cropW, 10) || 0,
            height: parseInt(image.dataset.cropH, 10) || 0
        };
    }

    function syncInputs(cropper) {
        // getData(true) renvoie des entiers en coordonnées de l'image naturelle.
        var d = cropper.getData(true);
        inputX.value = Math.max(0, d.x);
        inputY.value = Math.max(0, d.y);
        inputW.value = Math.max(0, d.width);
        inputH.value = Math.max(0, d.height);
    }

    var cropper = new Cropper(image, {
        viewMode: 1,
        autoCropArea: 1,
        background: false,
        responsive: true,
        ready: function () {
            if (initial) {
                cropper.setData(initial);
            }
            syncInputs(cropper);
        },
        crop: function () {
            syncInputs(cropper);
        }
    });

    // Boutons de format : verrouillent le ratio du Cropper. data-ratio vide =
    // recadrage libre (NaN côté Cropper). Le ratio n'est pas stocké : il est
    // encodé par les dimensions du crop renvoyées au serveur.
    var buttons = document.querySelectorAll('.ratio-btn');
    Array.prototype.forEach.call(buttons, function (btn) {
        btn.addEventListener('click', function () {
            var raw = btn.getAttribute('data-ratio');
            var ratio = raw ? parseFloat(raw) : NaN;
            cropper.setAspectRatio(isNaN(ratio) ? NaN : ratio);
            Array.prototype.forEach.call(buttons, function (b) {
                b.classList.toggle('is-active', b === btn);
            });
        });
    });
})();
