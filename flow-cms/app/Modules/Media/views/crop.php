<?php
/** @var \App\Modules\Media\Media $media */
/** @var string $error */
/** @var string $base */

use App\Core\Config;
use App\Modules\Media\MediaUrl;

$ratios = (array) Config::get('media.ratios', []);
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

    <?php if ($ratios !== []): ?>
        <div class="ratio-bar" role="group" aria-label="Format de recadrage">
            <span class="ratio-label">Format :</span>
            <?php foreach ($ratios as $key => $r): ?>
                <button type="button" class="ratio-btn" data-ratio="<?= e($r['value'] ?? '') ?>">
                    <?= e($r['label'] ?? $key) ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
    .ratio-bar { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; margin:.2rem 0 .9rem; }
    .ratio-label { color:var(--muted); font-size:.9rem; margin-right:.2rem; }
    /* color/box-shadow explicites : le thème admin force color:#fff + ombre sur tout <button>. */
    .ratio-btn { padding:.35rem .7rem; border:1px solid var(--line); border-radius:6px;
        background:var(--panel); color:var(--txt); cursor:pointer; font-size:.85rem;
        box-shadow:none; }
    .ratio-btn:hover { background:var(--accent-soft); color:var(--accent-600);
        filter:none; box-shadow:none; }
    .ratio-btn.is-active { background:var(--grad); border-color:transparent; color:#fff; }
</style>

<script src="<?= e($base . '/assets/vendor/cropper/cropper.min.js') ?>"></script>
<script src="<?= e($base . '/assets/js/media-crop.js') ?>"></script>
