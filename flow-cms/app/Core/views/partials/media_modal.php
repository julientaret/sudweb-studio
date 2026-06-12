<?php
/**
 * Modale partagée de sélection d'image, pilotée par pages-editor.js.
 *
 * Réutilisée par les éditeurs de contenu (Pages, Agenda, Actualités) : un seul
 * #media-modal par page, le JS y mémorise le champ image actif. Affiche une
 * barre de filtres par dossier dès qu'au moins un dossier existe.
 *
 * @var array<int, \App\Modules\Media\Media>       $media   toutes les images
 * @var array<int, \App\Modules\Media\MediaFolder> $folders dossiers (avec ->count)
 */

use App\Modules\Media\MediaUrl;
?>
<div id="media-modal" class="media-modal" hidden>
    <div class="media-modal-backdrop" data-close></div>
    <div class="media-modal-panel" role="dialog" aria-modal="true" aria-label="Choisir une image">
        <div class="media-modal-head">
            <strong>Choisir une image</strong>
            <button type="button" class="media-modal-close link" data-close aria-label="Fermer">×</button>
        </div>
        <?php if ($media === []): ?>
            <p class="hint" style="padding:1rem;">Aucune image dans la bibliothèque. Ajoutez-en via le menu Médias.</p>
        <?php else: ?>
            <?php if ($folders !== []): ?>
                <?php $hasUnfiled = false; foreach ($media as $m) { if ($m->folderId === null) { $hasUnfiled = true; break; } } ?>
                <div class="media-modal-filters" aria-label="Filtrer par dossier">
                    <button type="button" class="media-filter active" data-folder="all">Tout</button>
                    <?php foreach ($folders as $f): ?>
                        <?php if ($f->count > 0): ?>
                            <button type="button" class="media-filter" data-folder="<?= e($f->id) ?>">
                                <?= e($f->name) ?> <span class="media-filter-count"><?= e($f->count) ?></span>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($hasUnfiled): ?>
                        <button type="button" class="media-filter" data-folder="none">Non classé</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="media-modal-grid">
                <?php foreach ($media as $m): ?>
                    <button type="button" class="media-pick"
                            data-id="<?= e($m->id) ?>"
                            data-folder="<?= e($m->folderId ?? '') ?>"
                            data-thumb="<?= e(MediaUrl::size($m, 'thumb')) ?>">
                        <img src="<?= e(MediaUrl::size($m, 'thumb')) ?>" alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
