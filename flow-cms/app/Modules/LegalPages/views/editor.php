<?php
/** @var ?\App\Modules\LegalPages\LegalPage $page */
/** @var string $error */
/** @var string $notice */
/** @var bool   $canPublish */
/** @var string $base */

$isEdit = $page !== null;
$action = $isEdit ? '/admin/pages-legales/edit' : '/admin/pages-legales/create';
$status = $page?->status ?? 'published';

// Delta initial sérialisé, passé au JS via data-* (instancie Quill).
$delta = $page?->content ?? ['ops' => []];
$json = (string) json_encode($delta, JSON_UNESCAPED_UNICODE);
?>
<link rel="stylesheet" href="<?= e($base . '/assets/vendor/quill/quill.snow.css') ?>">
<link rel="stylesheet" href="<?= e($base . '/assets/css/block-editor.css') ?>">

<div class="actions" style="justify-content:space-between; margin-bottom:1.6rem;">
    <h1 style="margin:0;"><?= $isEdit ? 'Modifier la page légale' : 'Nouvelle page légale' ?></h1>
    <a class="btn-secondary" href="<?= e($base . '/admin/pages-legales') ?>">← Retour</a>
</div>

<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<form method="post" action="<?= e($base . $action) ?>">
    <?= \App\Core\Csrf::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($page->id) ?>"><?php endif; ?>

    <div class="card">
        <label for="title">Titre de la page</label>
        <input type="text" id="title" name="title" value="<?= e($page->title ?? '') ?>" required>

        <label for="slug">Slug (URL) — laisser vide pour générer depuis le titre</label>
        <input type="text" id="slug" name="slug" value="<?= e($page->slug ?? '') ?>" placeholder="ex. mentions-legales">

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

    <h2 class="section-title">Contenu</h2>
    <div class="card">
        <div class="quill-editor" id="quill-content"
             data-quill-input="quill-input-content"
             data-quill-delta="<?= e($json) ?>"></div>
        <input type="hidden" id="quill-input-content" name="content" value="<?= e($json) ?>">
    </div>

    <div class="actions form-actions-row" style="margin-top:1.4rem;">
        <button type="submit">Enregistrer</button>
        <a class="btn-secondary" href="<?= e($base . '/admin/pages-legales') ?>">Annuler</a>
    </div>
</form>

<style>
    #title, #slug { margin-bottom:1.2rem; }
    .quill-editor { min-height:320px; background:#fff; }
</style>

<script src="<?= e($base . '/assets/vendor/quill/quill.js') ?>"></script>
<script src="<?= e($base . '/assets/js/legal-editor.js') ?>"></script>
