<?php
/** @var ?\App\Modules\Users\User $user */
/** @var string $error */
/** @var array<string, string> $values */
/** @var array<int, string> $roles */
/** @var string $base */

$isEdit = $user !== null;
$email = $values['email'] ?? ($user->email ?? '');
$name  = $values['name'] ?? ($user->name ?? '');
$role  = $values['role'] ?? ($user->role ?? 'editor');
$action = $isEdit ? '/admin/users/edit' : '/admin/users/create';
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;"><?= $isEdit ? 'Modifier un compte' : 'Nouveau compte' ?></h1>
    <a class="btn-secondary" href="<?= e($base . '/admin/users') ?>">← Comptes</a>
</div>

<div class="card" style="max-width:620px; margin-top:1.3rem;">
    <?php if ($error !== ''): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($base . $action) ?>">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= e($user->id) ?>">
        <?php endif; ?>

        <div class="field-grid">
            <div class="field">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" placeholder="Jeanne Dupont">
            </div>
            <div class="field">
                <label for="email">Adresse e-mail <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="jeanne@exemple.fr" required>
            </div>
        </div>

        <div class="field">
            <label for="role">Rôle</label>
            <select id="role" name="role">
                <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r) ?>" <?= $r === $role ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="hint">Le rôle détermine les permissions (édition, publication, gestion des comptes…).</p>
        </div>

        <div class="field">
            <label for="password">Mot de passe <?= $isEdit ? '' : '<span class="req">*</span>' ?></label>
            <input type="password" id="password" name="password" placeholder="<?= $isEdit ? 'Laisser vide pour conserver' : '8 caractères minimum' ?>" <?= $isEdit ? '' : 'required' ?>>
            <p class="hint"><?= $isEdit ? 'Renseignez-le seulement pour le changer.' : 'Au moins 8 caractères.' ?></p>
        </div>

        <div class="form-actions">
            <button type="submit"><?= $isEdit ? 'Enregistrer' : 'Créer le compte' ?></button>
            <a class="btn-secondary" href="<?= e($base . '/admin/users') ?>">Annuler</a>
        </div>
    </form>
</div>
