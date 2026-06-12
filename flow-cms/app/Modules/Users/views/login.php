<?php
/** @var string $error */
/** @var string $email */
/** @var string $base */
?>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($base . '/admin/login') ?>">
        <?= \App\Core\Csrf::field() ?>

        <div class="field">
            <label for="email">Adresse e-mail</label>
            <div class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="vous@exemple.fr" required autofocus>
            </div>
        </div>

        <div class="field">
            <label for="password">Mot de passe</label>
            <div class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="block" style="margin-top:.6rem">Se connecter</button>
    </form>
</div>
