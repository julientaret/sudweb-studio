<?php
/** @var array<int, \App\Modules\Users\User> $users */
/** @var string $base */
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Utilisateurs</h1>
    <a class="btn" href="<?= e($base . '/admin/users/create') ?>">+ Nouveau compte</a>
</div>
<div class="card" style="margin-top:1.2rem;">
    <table>
        <thead>
            <tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Créé le</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ($users === []): ?>
            <tr><td colspan="5">Aucun compte.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u->name) ?></td>
                <td><?= e($u->email) ?></td>
                <td><?= e($u->role) ?></td>
                <td><?= e($u->createdAt) ?></td>
                <td>
                    <div class="actions">
                        <a class="btn-secondary" href="<?= e($base . '/admin/users/edit?id=' . $u->id) ?>">Modifier</a>
                        <form method="post" action="<?= e($base . '/admin/users/delete') ?>"
                              onsubmit="return confirm('Supprimer ce compte ?');" style="display:inline">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($u->id) ?>">
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
    td .actions { gap:.5rem; align-items:center; }
    td .actions .btn-secondary,
    td .actions .btn-danger { display:inline-flex; align-items:center; line-height:1.2;
        padding:.5rem .9rem; font-size:.85rem; border-radius:9px; border:1px solid; }
    .btn-danger { background:#fff; border-color:#f1c4c4; color:#c0322f; cursor:pointer; }
    .btn-danger:hover { background:#fdecec; filter:none; box-shadow:none; }
</style>
