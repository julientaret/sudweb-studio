<?php
/** @var array<int, \App\Modules\Agenda\Event> $events */
/** @var string $notice */
/** @var bool   $canPublish */
/** @var string $base */

/** Formatte une date Y-m-d en jj/mm/aaaa (affichage). */
$fmt = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '';
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $d !== false ? $d->format('d/m/Y') : $date;
};
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Agenda</h1>
    <a class="btn" href="<?= e($base . '/admin/agenda/create') ?>">+ Nouvel événement</a>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<div class="card" style="margin-top:1.2rem;">
    <table>
        <thead>
            <tr><th>Titre</th><th>Dates</th><th>Statut</th><th>Modifié</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ($events === []): ?>
            <tr><td colspan="5">Aucun événement.</td></tr>
        <?php endif; ?>
        <?php foreach ($events as $ev): ?>
            <tr id="e<?= e($ev->id) ?>">
                <td>
                    <?php if ($ev->isPublished()): ?>
                        <a href="<?= e($base . '/agenda/' . $ev->slug) ?>" target="_blank" rel="noopener"><?= e($ev->title) ?></a>
                    <?php else: ?>
                        <?= e($ev->title) ?>
                    <?php endif; ?>
                </td>
                <td class="muted">
                    <?= e($fmt($ev->startDate)) ?>
                    <?php if ($ev->endDate !== null): ?> → <?= e($fmt($ev->endDate)) ?><?php endif; ?>
                </td>
                <td>
                    <span class="status status-<?= e($ev->status) ?>">
                        <?= $ev->isPublished() ? 'Publié' : 'Brouillon' ?>
                    </span>
                </td>
                <td class="muted"><?= e($ev->updatedAt) ?></td>
                <td>
                    <div class="actions">
                        <a class="btn-secondary" href="<?= e($base . '/admin/agenda/edit?id=' . $ev->id) ?>">Modifier</a>
                        <form method="post" action="<?= e($base . '/admin/agenda/delete') ?>"
                              onsubmit="return confirm('Supprimer cet événement ?');" style="display:inline">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($ev->id) ?>">
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
