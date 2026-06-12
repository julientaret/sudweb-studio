<?php
/** @var \App\Modules\Media\Media $media */
/** @var string $error */
/** @var string $base */

use App\Modules\Media\MediaUrl;
?>
<link rel="stylesheet" href="<?= e($base . '/assets/vendor/cropper/cropper.min.css') ?>">

<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Recadrer</h1>
    <a href="<?= e($base . '/admin/media') ?>">← Retour à la bibliothèque</a>
</div>

<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<div class="card" style="margin-top:1.2rem;">
    <p class="muted" style="margin-top:0;">
        <strong><?= e($media->originalName) ?></strong> — original <?= e($media->width) ?>×<?= e($media->height) ?> px.
        Le recadrage est <em>non destructif</em> : l'original est conservé, seules les déclinaisons sont régénérées.
    </p>

    <div class="crop-stage">
        <img id="cropper-image"
             src="<?= e(MediaUrl::original($media, true)) ?>"
             alt="<?= e($media->alt) ?>"
             <?php if ($media->hasCrop()): ?>
             data-crop-x="<?= e($media->cropX) ?>" data-crop-y="<?= e($media->cropY) ?>"
             data-crop-w="<?= e($media->cropW) ?>" data-crop-h="<?= e($media->cropH) ?>"
             <?php endif; ?>>
    </div>

    <form method="post" action="<?= e($base . '/admin/media/crop') ?>" id="crop-form" style="margin-top:1rem;">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= e($media->id) ?>">
        <input type="hidden" name="x" id="crop-x" value="0">
        <input type="hidden" name="y" id="crop-y" value="0">
        <input type="hidden" name="w" id="crop-w" value="0">
        <input type="hidden" name="h" id="crop-h" value="0">
        <div class="actions">
            <button type="submit">Appliquer le recadrage</button>
            <?php if ($media->hasCrop()): ?>
                <span class="sep"></span>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($media->hasCrop()): ?>
        <form method="post" action="<?= e($base . '/admin/media/crop/reset') ?>" style="margin-top:.5rem;">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= e($media->id) ?>">
            <button type="submit" class="link">Réinitialiser (revenir à l'original entier)</button>
        </form>
    <?php endif; ?>
</div>

<style>
    .muted { color:var(--muted); font-size:.9rem; }
    .crop-stage { max-width:100%; background:#eef0f3; border-radius:8px; }
    .crop-stage img { display:block; max-width:100%; }
</style>

<script src="<?= e($base . '/assets/vendor/cropper/cropper.min.js') ?>"></script>
<script src="<?= e($base . '/assets/js/media-crop.js') ?>"></script>
