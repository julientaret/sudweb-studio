<?php
/** @var array<int, \App\Modules\Documents\Document> $items */
/** @var array<int, \App\Modules\Documents\DocumentFolder> $folders */
/** @var int    $unfiled   nombre de documents non classés */
/** @var int    $total     nombre total de documents */
/** @var string $current   'all' | 'none' | id (string) */
/** @var bool   $canManage */
/** @var string $notice */
/** @var string $error */
/** @var string $base */

use App\Modules\Documents\DocumentUrl;

$allowed = (array) \App\Core\Config::get('documents.allowed', []);
$extList = array_keys($allowed);
$accept = '.' . implode(',.', $extList);
// Limite réellement applicable = min(config, limites PHP du serveur). On affiche
// et on contrôle côté client cette valeur, jamais le plafond théorique seul.
$maxSize = \App\Modules\Documents\DocumentProcessor::effectiveMaxSize();
$maxMo = max(1, (int) floor($maxSize / (1024 * 1024)));

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

/** Taille lisible (Ko / Mo). */
$humanSize = static function (int $bytes): string {
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1, ',', ' ') . ' Mo';
    }
    return max(1, (int) round($bytes / 1024)) . ' Ko';
};

/** Famille de couleur selon l'extension (pour la pastille de type). */
$family = static function (string $ext): string {
    return match ($ext) {
        'pdf'                       => 'pdf',
        'doc', 'docx', 'odt', 'rtf' => 'doc',
        'xls', 'xlsx', 'ods', 'csv' => 'xls',
        'ppt', 'pptx', 'odp'        => 'ppt',
        default                     => 'txt',
    };
};
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Documents</h1>
    <span class="muted"><?= $total ?> document<?= $total > 1 ? 's' : '' ?></span>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<!-- Barre de dossiers -->
<div class="folder-bar">
    <a class="chip <?= $current === 'all' ? 'active' : '' ?>" href="<?= e($base . '/admin/documents') ?>">
        Tous <span class="n"><?= $total ?></span>
    </a>
    <a class="chip <?= $current === 'none' ? 'active' : '' ?>" href="<?= e($base . '/admin/documents?folder=none') ?>">
        Non classé <span class="n"><?= $unfiled ?></span>
    </a>
    <?php foreach ($folders as $f): ?>
        <a class="chip <?= $current === (string) $f->id ? 'active' : '' ?>" href="<?= e($base . '/admin/documents?folder=' . $f->id) ?>">
            <?= e($f->name) ?> <span class="n"><?= $f->count ?></span>
        </a>
    <?php endforeach; ?>

    <?php if ($canManage): ?>
        <form method="post" action="<?= e($base . '/admin/documents/folder/create') ?>" class="folder-new">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="folder" value="<?= e($current) ?>">
            <input type="text" name="name" placeholder="Nouveau dossier…" maxlength="120">
            <button type="submit" class="ghost">+ Dossier</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($canManage && $currentFolder !== null): ?>
    <div class="folder-tools">
        <form method="post" action="<?= e($base . '/admin/documents/folder/rename') ?>" class="folder-rename">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $currentFolder ?>">
            <input type="text" name="name" value="<?= e($currentName) ?>">
            <button type="submit" class="secondary">Renommer</button>
        </form>
        <form method="post" action="<?= e($base . '/admin/documents/folder/delete') ?>"
              onsubmit="return confirm('Supprimer ce dossier ? Les documents seront conservés (Non classé).');">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $currentFolder ?>">
            <button type="submit" class="link danger">Supprimer le dossier</button>
        </form>
    </div>
<?php endif; ?>

<!-- Upload (cible le dossier courant). Sans JS : formulaire classique mono-fichier.
     Avec JS (documents-upload.js) : glisser-déposer multiple + barres de progression. -->
<div class="card upload-card" id="dropzone" style="margin:1.1rem 0;"
     data-upload-url="<?= e($base . '/admin/documents/upload/ajax') ?>"
     data-reload-url="<?= e($base . '/admin/documents' . ($current !== 'all' ? '?folder=' . rawurlencode($current) : '')) ?>"
     data-folder="<?= e($current) ?>"
     data-exts="<?= e(implode(',', $extList)) ?>"
     data-max-size="<?= $maxSize ?>">
    <form method="post" action="<?= e($base . '/admin/documents/upload') ?>" enctype="multipart/form-data" id="upload-form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="folder" value="<?= e($current) ?>">
        <div class="upload-row">
            <div class="upload-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div>
                    <strong class="upload-title">Ajouter un document<?= $currentFolder !== null ? ' dans ce dossier' : '' ?></strong>
                    <p class="hint" style="margin:.15rem 0 0;">PDF, Word, Excel, PowerPoint, OpenDocument, texte · <?= $maxMo ?> Mo max</p>
                </div>
            </div>
            <input type="file" id="file" name="file" accept="<?= e($accept) ?>" multiple required>
            <button type="submit" class="upload-submit">Téléverser</button>
        </div>
    </form>
    <ul class="upload-progress" id="upload-progress" hidden></ul>
</div>

<?php if ($items === []): ?>
    <div class="card">Aucun document dans cette vue.</div>
<?php else: ?>
    <div class="doc-grid">
        <?php foreach ($items as $d): ?>
            <?php $url = DocumentUrl::public($d); ?>
            <article class="doc-item card">
                <div class="doc-head">
                    <a class="doc-icon fam-<?= e($family($d->ext)) ?>" href="<?= e($url) ?>" target="_blank" rel="noopener" title="Ouvrir">
                        <span class="doc-ext"><?= e(strtoupper($d->ext)) ?></span>
                    </a>
                    <div class="doc-meta">
                        <strong class="doc-name"><?= e($d->label()) ?></strong>
                        <span class="muted"><?= e($allowed[$d->ext] ?? strtoupper($d->ext)) ?> · <?= e($humanSize($d->size)) ?></span>
                        <?php if ($d->title !== '' && $d->title !== $d->originalName): ?>
                            <span class="muted doc-orig" title="Nom du fichier d'origine"><?= e($d->originalName) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post" action="<?= e($base . '/admin/documents/title') ?>" class="title-form">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= e($d->id) ?>">
                    <input type="hidden" name="folder" value="<?= e($current) ?>">
                    <input type="text" name="title" value="<?= e($d->title) ?>" placeholder="Libellé (réutilisable)">
                    <button type="submit" class="link">Libellé</button>
                </form>

                <form method="post" action="<?= e($base . '/admin/documents/move') ?>" class="move-form">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= e($d->id) ?>">
                    <input type="hidden" name="folder" value="<?= e($current) ?>">
                    <select name="folder_id"><?= $moveOptions($d->folderId) ?></select>
                    <button type="submit" class="link">Déplacer</button>
                </form>

                <div class="card-actions">
                    <a class="btn-sm" href="<?= e($url) ?>" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Ouvrir
                    </a>
                    <button type="button" class="btn-sm" data-copy-link="<?= e($url) ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        Copier le lien
                    </button>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= e($base . '/admin/documents/delete') ?>"
                              onsubmit="return confirm('Supprimer ce document ?');">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($d->id) ?>">
                            <input type="hidden" name="folder" value="<?= e($current) ?>">
                            <button type="submit" class="btn-sm danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                Supprimer
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
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
    .doc-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
    @media (max-width:900px) { .doc-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width:560px) { .doc-grid { grid-template-columns:1fr; } }
    .doc-item { margin:0; padding:1rem; transition:box-shadow .15s, transform .12s; }
    .doc-item:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
    .doc-head { display:flex; gap:.85rem; align-items:flex-start; margin-bottom:.8rem; }
    .doc-icon { flex:0 0 auto; width:54px; height:62px; border-radius:9px; display:grid; place-items:center;
                text-decoration:none; position:relative; color:#fff; box-shadow:var(--shadow-sm);
                background:#8a93a6; }
    .doc-icon::before { content:""; position:absolute; top:0; right:0; border-width:0 13px 13px 0;
                        border-style:solid; border-color:rgba(255,255,255,.55) transparent; border-radius:0 9px 0 0; }
    .doc-ext { font-size:.72rem; font-weight:800; letter-spacing:.02em; }
    .fam-pdf { background:#e5484d; }
    .fam-doc { background:#2b6cb0; }
    .fam-xls { background:#1f9d57; }
    .fam-ppt { background:#dd6b20; }
    .fam-txt { background:#6b7280; }
    .doc-meta { min-width:0; display:flex; flex-direction:column; gap:.15rem; }
    .doc-name { font-size:.95rem; word-break:break-word; }
    .doc-orig { font-size:.76rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .title-form, .move-form { display:flex; gap:.4rem; align-items:center; margin-bottom:.5rem; }
    .title-form input[type=text] { margin:0; padding:.45rem .6rem; font-size:.83rem; }
    .move-form select { margin:0; padding:.45rem .5rem; font-size:.83rem; flex:1; }
    .title-form button, .move-form button { margin:0; font-size:.82rem; }
    /* Boutons d'action de carte */
    .card-actions { display:flex; gap:.5rem; margin-top:.2rem; flex-wrap:wrap; }
    .card-actions > a, .card-actions > button, .card-actions > form { flex:1 1 0; min-width:0; }
    .btn-sm { width:100%; display:inline-flex; align-items:center; justify-content:center; gap:.35rem;
              padding:.5rem .6rem; font-size:.84rem; font-weight:600; border-radius:9px; cursor:pointer;
              text-decoration:none; box-shadow:none; }
    .btn-sm svg { width:15px; height:15px; }
    a.btn-sm, button.btn-sm:not(.danger) { background:#fff; border:1px solid var(--line); color:var(--txt); }
    a.btn-sm:hover, button.btn-sm:not(.danger):hover { border-color:var(--accent); color:var(--accent-600); filter:none; box-shadow:none; }
    button.btn-sm.copied { border-color:#1f9d57; color:#1f9d57; }
    button.btn-sm.danger { background:#fff; border:1px solid #f1c4c4; color:#c0322f; }
    button.btn-sm.danger:hover { background:#fdecec; filter:none; box-shadow:none; }
</style>

<script src="<?= e($base . '/assets/js/documents-upload.js') ?>"></script>
