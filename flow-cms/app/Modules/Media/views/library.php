<?php
/** @var array<int, \App\Modules\Media\Media> $items */
/** @var array<int, \App\Modules\Media\MediaFolder> $folders */
/** @var int    $unfiled   nombre de médias non classés */
/** @var int    $total     nombre total de médias */
/** @var string $current   'all' | 'none' | id (string) */
/** @var bool   $canManage */
/** @var string $notice */
/** @var string $error */
/** @var string $base */

use App\Modules\Media\MediaUrl;

$currentFolder = ($current !== 'all' && $current !== 'none' && ctype_digit($current)) ? (int) $current : null;
$currentName = '';
foreach ($folders as $f) {
    if ($f->id === $currentFolder) {
        $currentName = $f->name;
    }
}

/** Options du select de déplacement. */
$moveOptions = static function (?int $selected) use ($folders): string {
    $html = '<option value="none"' . ($selected === null ? ' selected' : '') . '>Non classé</option>';
    foreach ($folders as $f) {
        $html .= '<option value="' . $f->id . '"' . ($selected === $f->id ? ' selected' : '') . '>' . e($f->name) . '</option>';
    }
    return $html;
};
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Médias</h1>
    <span class="muted"><?= $total ?> image<?= $total > 1 ? 's' : '' ?></span>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<!-- Barre de dossiers -->
<div class="folder-bar">
    <a class="chip <?= $current === 'all' ? 'active' : '' ?>" href="<?= e($base . '/admin/media') ?>">
        Tous <span class="n"><?= $total ?></span>
    </a>
    <a class="chip <?= $current === 'none' ? 'active' : '' ?>" href="<?= e($base . '/admin/media?folder=none') ?>">
        Non classé <span class="n"><?= $unfiled ?></span>
    </a>
    <?php foreach ($folders as $f): ?>
        <a class="chip <?= $current === (string) $f->id ? 'active' : '' ?>" href="<?= e($base . '/admin/media?folder=' . $f->id) ?>">
            <?= e($f->name) ?> <span class="n"><?= $f->count ?></span>
        </a>
    <?php endforeach; ?>

    <?php if ($canManage): ?>
        <form method="post" action="<?= e($base . '/admin/media/folder/create') ?>" class="folder-new">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="folder" value="<?= e($current) ?>">
            <input type="text" name="name" placeholder="Nouveau dossier…" maxlength="120">
            <button type="submit" class="ghost">+ Dossier</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($canManage && $currentFolder !== null): ?>
    <div class="folder-tools">
        <form method="post" action="<?= e($base . '/admin/media/folder/rename') ?>" class="folder-rename">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $currentFolder ?>">
            <input type="text" name="name" value="<?= e($currentName) ?>">
            <button type="submit" class="secondary">Renommer</button>
        </form>
        <form method="post" action="<?= e($base . '/admin/media/folder/delete') ?>"
              onsubmit="return confirm('Supprimer ce dossier ? Les images seront conservées (Non classé).');">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $currentFolder ?>">
            <button type="submit" class="link danger">Supprimer le dossier</button>
        </form>
    </div>
<?php endif; ?>

<!-- Upload (cible le dossier courant). Sans JS : formulaire classique mono-fichier.
     Avec JS (media-upload.js) : glisser-déposer multiple + barres de progression. -->
<div class="card upload-card" id="dropzone" style="margin:1.1rem 0;"
     data-upload-url="<?= e($base . '/admin/media/upload/ajax') ?>"
     data-reload-url="<?= e($base . '/admin/media' . ($current !== 'all' ? '?folder=' . rawurlencode($current) : '')) ?>"
     data-folder="<?= e($current) ?>"
     data-max-size="<?= (int) \App\Core\Config::get('media.max_size', 5 * 1024 * 1024) ?>">
    <form method="post" action="<?= e($base . '/admin/media/upload') ?>" enctype="multipart/form-data" id="upload-form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="folder" value="<?= e($current) ?>">
        <div class="upload-row">
            <div class="upload-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div>
                    <strong class="upload-title">Ajouter une image<?= $currentFolder !== null ? ' dans ce dossier' : '' ?></strong>
                    <p class="hint" style="margin:.15rem 0 0;">JPEG, PNG, WebP ou GIF · 5 Mo max</p>
                </div>
            </div>
            <input type="file" id="file" name="file" accept="image/*" multiple required>
            <button type="submit" class="upload-submit">Téléverser</button>
        </div>
    </form>
    <ul class="upload-progress" id="upload-progress" hidden></ul>
</div>

<?php if ($items === []): ?>
    <div class="card">Aucune image dans cette vue.</div>
<?php else: ?>
    <div class="media-grid">
        <?php foreach ($items as $m): ?>
            <figure class="media-item card">
                <a class="media-thumb" href="<?= e(MediaUrl::original($m)) ?>" target="_blank" rel="noopener">
                    <img src="<?= e(MediaUrl::size($m, 'tablet', true)) ?>"
                         alt="<?= e($m->alt) ?>" loading="lazy">
                    <?php if ($m->hasCrop()): ?><span class="badge">recadrée</span><?php endif; ?>
                </a>
                <figcaption>
                    <div class="meta">
                        <strong><?= e($m->originalName) ?></strong><br>
                        <span class="muted"><?= e($m->width) ?>×<?= e($m->height) ?> ·
                        <?= e(round($m->size / 1024)) ?> Ko</span>
                    </div>

                    <form method="post" action="<?= e($base . '/admin/media/alt') ?>" class="alt-form">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= e($m->id) ?>">
                        <input type="hidden" name="folder" value="<?= e($current) ?>">
                        <input type="text" name="alt" value="<?= e($m->alt) ?>" placeholder="Texte alternatif (alt)">
                        <button type="submit" class="link">Alt</button>
                    </form>

                    <form method="post" action="<?= e($base . '/admin/media/move') ?>" class="move-form">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= e($m->id) ?>">
                        <input type="hidden" name="folder" value="<?= e($current) ?>">
                        <select name="folder_id"><?= $moveOptions($m->folderId) ?></select>
                        <button type="submit" class="link">Déplacer</button>
                    </form>

                    <div class="card-actions">
                        <a class="btn-sm" href="<?= e($base . '/admin/media/edit?id=' . $m->id) ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                            Recadrer
                        </a>
                        <?php if ($canManage): ?>
                            <form method="post" action="<?= e($base . '/admin/media/delete') ?>"
                                  onsubmit="return confirm('Supprimer cette image ?');">
                                <?= \App\Core\Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= e($m->id) ?>">
                                <input type="hidden" name="folder" value="<?= e($current) ?>">
                                <button type="submit" class="btn-sm danger">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Supprimer
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
    .muted { color:var(--muted); font-size:.85rem; }
    /* Barre de dossiers */
    .folder-bar { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; margin:1.2rem 0 .4rem; }
    .chip { display:inline-flex; align-items:center; gap:.45rem; padding:.42rem .8rem; border-radius:999px;
            background:#fff; border:1px solid var(--line); color:var(--txt); text-decoration:none;
            font-size:.88rem; font-weight:500; transition:.15s; }
    .chip:hover { border-color:var(--accent); color:var(--accent-600); }
    .chip.active { background:var(--accent-soft); border-color:transparent; color:var(--accent-600); font-weight:600; }
    .chip .n { font-size:.72rem; color:var(--muted); background:var(--bg); padding:.05rem .4rem; border-radius:999px; }
    .chip.active .n { color:var(--accent-600); background:#fff; }
    .folder-new { display:flex; align-items:center; gap:.4rem; margin-left:auto; }
    .folder-new input { width:170px; padding:.42rem .7rem; font-size:.85rem; }
    .folder-new button { padding:.42rem .8rem; }
    .folder-tools { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; margin:.3rem 0 0; }
    .folder-rename { display:flex; gap:.4rem; align-items:center; }
    .folder-rename input { padding:.4rem .65rem; font-size:.85rem; width:200px; }
    .folder-rename button { padding:.4rem .8rem; }
    /* Upload */
    .upload-card { border-style:dashed; border-color:#d7dbec; background:#fcfcff; transition:border-color .15s, background .15s; }
    .upload-row { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
    .upload-text { display:flex; align-items:center; gap:.8rem; flex:1; min-width:220px; }
    .upload-text svg { width:34px; height:34px; color:var(--accent); flex:0 0 auto; }
    .upload-text strong { font-size:.95rem; }
    .upload-row input[type=file] { flex:2; min-width:240px; }
    /* Dropzone (JS actif) */
    .upload-card.js-dropzone { cursor:pointer; }
    .upload-card.js-dropzone .upload-row input[type=file],
    .upload-card.js-dropzone .upload-submit { display:none; }
    .upload-card.dragover { border-color:var(--accent); background:var(--accent-soft); }
    .upload-card.dragover .upload-text svg { transform:translateY(-2px); }
    /* Liste de progression */
    .upload-progress { list-style:none; margin:.9rem 0 0; padding:0; display:flex; flex-direction:column; gap:.5rem; }
    .upload-progress li { display:flex; align-items:center; gap:.7rem; font-size:.85rem; }
    .upload-progress .up-name { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .upload-progress .up-bar { flex:0 0 140px; height:6px; border-radius:999px; background:var(--bg); overflow:hidden; }
    .upload-progress .up-bar > i { display:block; height:100%; width:0; background:var(--grad); transition:width .15s ease; }
    .upload-progress .up-status { flex:0 0 auto; font-size:.78rem; color:var(--muted); min-width:42px; text-align:right; }
    .upload-progress li.done .up-bar > i { width:100% !important; background:#1f9d57; }
    .upload-progress li.done .up-status { color:#1f9d57; }
    .upload-progress li.error .up-bar > i { width:100% !important; background:#c0322f; }
    .upload-progress li.error .up-status { color:#c0322f; font-weight:600; }
    .upload-progress li.error .up-name { color:#c0322f; }
    /* Grille : 3 cartes par ligne */
    .media-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
    @media (max-width:900px) { .media-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width:560px) { .media-grid { grid-template-columns:1fr; } }
    .media-item { margin:0; padding:.8rem; transition:box-shadow .15s, transform .12s; }
    .media-item:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
    .media-thumb { position:relative; display:block; }
    .media-item img { width:100%; height:auto; aspect-ratio:4/3; object-fit:cover; border-radius:10px; background:#eef0f3; }
    .badge { position:absolute; top:.5rem; left:.5rem; background:var(--grad); color:#fff; font-size:.68rem; font-weight:600; padding:.18rem .5rem; border-radius:999px; box-shadow:0 2px 6px rgba(43,139,255,.4); }
    .media-item figcaption { margin-top:.7rem; }
    .media-item .meta { margin-bottom:.6rem; word-break:break-word; }
    .alt-form, .move-form { display:flex; gap:.4rem; align-items:center; margin-bottom:.5rem; }
    .alt-form input[type=text] { margin:0; padding:.45rem .6rem; font-size:.83rem; }
    .move-form select { margin:0; padding:.45rem .5rem; font-size:.83rem; flex:1; }
    .alt-form button, .move-form button { margin:0; font-size:.82rem; }
    /* Boutons d'action de carte */
    .card-actions { display:flex; gap:.5rem; margin-top:.2rem; }
    .card-actions > a, .card-actions > form { flex:1; }
    .btn-sm { width:100%; display:inline-flex; align-items:center; justify-content:center; gap:.35rem;
              padding:.5rem .6rem; font-size:.84rem; font-weight:600; border-radius:9px; cursor:pointer;
              text-decoration:none; box-shadow:none; }
    .btn-sm svg { width:15px; height:15px; }
    a.btn-sm { background:#fff; border:1px solid var(--line); color:var(--txt); }
    a.btn-sm:hover { border-color:var(--accent); color:var(--accent-600); }
    button.btn-sm.danger { background:#fff; border:1px solid #f1c4c4; color:#c0322f; }
    button.btn-sm.danger:hover { background:#fdecec; filter:none; box-shadow:none; }
</style>

<script src="<?= e($base . '/assets/js/media-upload.js') ?>"></script>
