<?php
/** @var ?\App\Modules\Realisations\Realisation $item */
/** @var string $error */
/** @var string $notice */
/** @var bool $mediaEnabled */
/** @var array<int, \App\Modules\Media\Media> $media */
/** @var array<int, \App\Modules\Media\MediaFolder> $folders */
/** @var ?\App\Modules\Media\Media $photo */
/** @var bool $canPublish */
/** @var string $base */

use App\Modules\Media\MediaUrl;

$isEdit = $item !== null;
$action = $isEdit ? '/admin/realisations/edit' : '/admin/realisations/create';
$status = $item?->status ?? 'draft';
$deltaJson = (string) json_encode($item->description ?? ['ops' => []], JSON_UNESCAPED_UNICODE);
?>
<link rel="stylesheet" href="<?= e($base . '/assets/vendor/quill/quill.snow.css') ?>">
<link rel="stylesheet" href="<?= e($base . '/assets/css/block-editor.css') ?>">

<div class="actions" style="justify-content:space-between; margin-bottom:1.6rem;">
    <h1 style="margin:0;"><?= $isEdit ? 'Modifier la réalisation' : 'Nouvelle réalisation' ?></h1>
    <a class="btn-secondary" href="<?= e($base . '/admin/realisations') ?>">← Retour</a>
</div>

<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<form method="post" action="<?= e($base . $action) ?>" id="collection-form">
    <?= \App\Core\Csrf::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($item->id) ?>"><?php endif; ?>

    <div class="card">
        <label for="title">Titre de la réalisation</label>
        <input type="text" id="title" name="title" value="<?= e($item->title ?? '') ?>" required>

        <label>Description</label>
        <div class="quill-editor" id="quill-description" data-quill-input="description"
             data-quill-delta="<?= e($deltaJson) ?>"></div>
        <input type="hidden" id="description" name="description" value="<?= e($deltaJson) ?>">

        <?php if ($mediaEnabled): ?>
        <label>Photo (bibliothèque média)</label>
        <div class="image-field" data-input="media-id">
            <?php if ($photo !== null): ?>
                <div class="image-preview"><img src="<?= e(MediaUrl::size($photo, 'thumb')) ?>" alt=""></div>
            <?php else: ?>
                <div class="image-preview empty"><span class="hint">Aucune image</span></div>
            <?php endif; ?>
            <input type="hidden" id="media-id" name="media_id" value="<?= e($item->mediaId ?? '') ?>">
            <div class="image-field-actions">
                <button type="button" class="image-choose">Choisir une image</button>
                <button type="button" class="image-clear"<?= $photo !== null ? '' : ' hidden' ?>>Retirer</button>
            </div>
        </div>
        <?php endif; ?>

        <label for="position">Ordre d'affichage (croissant)</label>
        <input type="number" id="position" name="position" value="<?= e($item->position ?? 0) ?>" step="1">

        <label>Statut</label>
        <?php if ($canPublish): ?>
            <div class="status-toggle">
                <input type="radio" id="status-draft" name="status" value="draft" <?= $status === 'draft' ? 'checked' : '' ?>>
                <label for="status-draft" class="toggle-opt">Brouillon</label>
                <input type="radio" id="status-pub" name="status" value="published" <?= $status === 'published' ? 'checked' : '' ?>>
                <label for="status-pub" class="toggle-opt">Publiée</label>
            </div>
        <?php else: ?>
            <input type="hidden" name="status" value="draft">
            <p class="muted">Publication réservée aux managers ; vous enregistrez en brouillon.</p>
        <?php endif; ?>
    </div>

    <div class="actions form-actions-row" style="margin-top:1.4rem;">
        <button type="submit">Enregistrer</button>
        <a class="btn-secondary" href="<?= e($base . '/admin/realisations') ?>">Annuler</a>
    </div>
</form>

<?php if ($mediaEnabled): ?>
<?= partial('partials/media_modal', ['media' => $media, 'folders' => $folders]) ?>
<?php endif; ?>

<style>
    #title, #position { margin-bottom:1.2rem; }
    .quill-editor { margin-bottom:1.2rem; }
</style>

<script src="<?= e($base . '/assets/vendor/quill/quill.js') ?>"></script>
<script src="<?= e($base . '/assets/js/collection-editor.js') ?>"></script>
