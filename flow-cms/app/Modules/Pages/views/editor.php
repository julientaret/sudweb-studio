<?php
/** @var ?\App\Modules\Pages\Page $page */
/** @var string $error */
/** @var array<int, array{key:string,cols:int,columns:array<int,array<int,array{key:string,type:string,label:string,form:string}>>}> $rows */
/** @var array<int, array{type:string,label:string,form:string}> $templates */
/** @var array<int, \App\Modules\Media\Media> $media */
/** @var array<int, \App\Modules\Media\MediaFolder> $folders */
/** @var bool $canPublish */
/** @var string $base */

$isEdit = $page !== null;
$action = $isEdit ? '/admin/pages/edit' : '/admin/pages/create';
$status = $page?->status ?? 'draft';

/** Carte d'un bloc (existant ou gabarit). */
$blockCard = static function (string $key, string $type, string $label, string $formHtml): string {
    ob_start(); ?>
    <div class="block-card" data-key="<?= e($key) ?>" data-type="<?= e($type) ?>">
        <div class="block-head">
            <span class="drag-handle" title="Glisser le bloc">⠿</span>
            <strong><?= e($label) ?></strong>
            <button type="button" class="block-remove link">Supprimer</button>
        </div>
        <input type="hidden" name="blocks[<?= e($key) ?>][type]" value="<?= e($type) ?>">
        <div class="block-body"><?= $formHtml ?></div>
    </div>
    <?php return (string) ob_get_clean();
};

/** Icônes SVG par type de bloc. */
$blockIcons = [
    'heading'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v16M18 4v16M6 12h12"/></svg>',
    'richtext' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="14" y2="18"/></svg>',
    'image'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>',
];

/** Barre d'ajout de blocs (présente dans chaque colonne). */
$addBar = static function () use ($templates, $blockIcons): string {
    $html = '<div class="col-add"><span class="col-add-label">Ajouter un bloc</span><div class="col-add-grid">';
    foreach ($templates as $t) {
        $icon = $blockIcons[$t['type']] ?? '';
        $html .= '<button type="button" class="add-block" data-type="' . e($t['type']) . '">'
            . '<span class="add-block-icon">' . $icon . '</span>'
            . '<span class="add-block-label">' . e($t['label']) . '</span>'
            . '</button>';
    }
    return $html . '</div></div>';
};

/** Une colonne avec ses cartes + la barre d'ajout. */
$columnHtml = static function (string $cardsHtml) use ($addBar): string {
    return '<div class="col"><div class="col-blocks">' . $cardsHtml . '</div>' . $addBar() . '</div>';
};

/** En-tête de ligne : poignée, sélecteur 1-4 colonnes, suppression. */
$rowHead = static function (int $cols): string {
    $buttons = '';
    for ($n = 1; $n <= 4; $n++) {
        $active = $n === $cols ? ' active' : '';
        $buttons .= '<button type="button" class="col-count' . $active . '" data-cols="' . $n . '">' . $n . '</button>';
    }
    return '<div class="row-head">'
        . '<span class="row-handle" title="Glisser la ligne">⠿ Ligne</span>'
        . '<span class="muted">Colonnes :</span><div class="col-counts">' . $buttons . '</div>'
        . '<button type="button" class="row-remove link">Supprimer la ligne</button>'
        . '</div>';
};

/** Une ligne complète. */
$rowHtml = static function (string $rkey, int $cols, string $columnsHtml) use ($rowHead): string {
    return '<div class="row" data-key="' . e($rkey) . '" data-cols="' . $cols . '">'
        . $rowHead($cols)
        . '<div class="row-cols">' . $columnsHtml . '</div>'
        . '</div>';
};
?>
<link rel="stylesheet" href="<?= e($base . '/assets/vendor/quill/quill.snow.css') ?>">
<link rel="stylesheet" href="<?= e($base . '/assets/css/block-editor.css') ?>">

<div class="actions" style="justify-content:space-between; margin-bottom:1.6rem;">
    <h1 style="margin:0;"><?= $isEdit ? 'Modifier la page' : 'Nouvelle page' ?></h1>
    <a class="btn-secondary" href="<?= e($base . '/admin/pages') ?>">← Retour</a>
</div>

<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (($notice ?? '') !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<form method="post" action="<?= e($base . $action) ?>" id="page-form">
    <?= \App\Core\Csrf::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($page->id) ?>"><?php endif; ?>
    <input type="hidden" name="layout" id="layout" value="">

    <div class="card">
        <label for="title">Titre de la page</label>
        <input type="text" id="title" name="title" value="<?= e($page->title ?? '') ?>" required>

        <label for="slug">Slug (URL) — laisser vide pour générer depuis le titre</label>
        <input type="text" id="slug" name="slug" value="<?= e($page->slug ?? '') ?>" placeholder="ex. notre-histoire">

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
    <div id="rows">
        <?php foreach ($rows as $row): ?>
            <?php
            $columnsHtml = '';
            foreach ($row['columns'] as $column) {
                $cards = '';
                foreach ($column as $b) {
                    $cards .= $blockCard($b['key'], $b['type'], $b['label'], $b['form']);
                }
                $columnsHtml .= $columnHtml($cards);
            }
            echo $rowHtml($row['key'], $row['cols'], $columnsHtml);
            ?>
        <?php endforeach; ?>
    </div>

    <div class="actions" style="margin-top:.8rem;">
        <button type="button" id="add-row" class="ghost">+ Ajouter une ligne</button>
    </div>

    <div class="actions form-actions-row" style="margin-top:1.4rem;">
        <button type="submit">Enregistrer</button>
        <a class="btn-secondary" href="<?= e($base . '/admin/pages') ?>">Annuler</a>
    </div>
</form>

<!-- Gabarits clonés par le JS -->
<template id="tpl-row"><?= $rowHtml('__RKEY__', 1, $columnHtml('')) ?></template>
<template id="tpl-col"><?= $columnHtml('') ?></template>
<?php foreach ($templates as $t): ?>
    <template id="tpl-<?= e($t['type']) ?>"><?= $blockCard('__KEY__', $t['type'], $t['label'], $t['form']) ?></template>
<?php endforeach; ?>

<!-- Modale partagée de sélection d'image (partial Core, piloté par le JS) -->
<?= partial('partials/media_modal', ['media' => $media, 'folders' => $folders]) ?>

<script src="<?= e($base . '/assets/vendor/quill/quill.js') ?>"></script>
<script src="<?= e($base . '/assets/js/pages-editor.js') ?>"></script>
