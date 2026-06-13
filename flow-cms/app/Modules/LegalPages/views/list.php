<?php
/** @var array<int, \App\Modules\LegalPages\LegalPage> $pages */
/** @var string $notice */
/** @var string $base */
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Pages légales</h1>
    <a class="btn" href="<?= e($base . '/admin/pages-legales/create') ?>">+ Nouvelle page</a>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<div class="card" style="margin-top:1.2rem;">
    <table>
        <thead>
            <tr><th>Titre</th><th>URL</th><th>Statut</th><th>Modifiée</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ($pages === []): ?>
            <tr><td colspan="5">Aucune page légale.</td></tr>
        <?php endif; ?>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td>
                    <?php if ($p->isPublished()): ?>
                        <a href="<?= e($base . '/' . $p->slug) ?>" target="_blank" rel="noopener"><?= e($p->title) ?></a>
                    <?php else: ?>
                        <?= e($p->title) ?>
                    <?php endif; ?>
                </td>
                <td class="muted">/<?= e($p->slug) ?></td>
                <td>
                    <span class="status status-<?= e($p->status) ?>">
                        <?= $p->isPublished() ? 'Publiée' : 'Brouillon' ?>
                    </span>
                </td>
                <td class="muted"><?= e($p->updatedAt) ?></td>
                <td>
                    <div class="actions">
                        <a class="btn-secondary" href="<?= e($base . '/admin/pages-legales/edit?id=' . $p->id) ?>">Modifier</a>
                        <form method="post" action="<?= e($base . '/admin/pages-legales/delete') ?>"
                              onsubmit="return confirm('Supprimer cette page légale ?');" style="display:inline">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($p->id) ?>">
                            <button type="submit" class="btn-danger">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .notice { background:#e7f6ec; border:1px solid #a8d5b9; color:#1c6b3a; padding:.7rem .9rem; border-radius:7px; margin-bottom:1rem; }
    .muted { color:var(--muted); }
    .status { font-size:.8rem; padding:.15rem .5rem; border-radius:5px; }
    .status-published { background:#e7f6ec; color:#1c6b3a; }
    .status-draft { background:#fdf0d5; color:#8a5a00; }
    td .actions { gap:.5rem; align-items:center; }
    td .actions .btn-secondary,
    td .actions .btn-danger { display:inline-flex; align-items:center; line-height:1.2;
        padding:.5rem .9rem; font-size:.85rem; border-radius:9px; border:1px solid; }
    .btn-danger { background:#fff; border-color:#f1c4c4; color:#c0322f; cursor:pointer; }
    .btn-danger:hover { background:#fdecec; filter:none; box-shadow:none; }
</style>
