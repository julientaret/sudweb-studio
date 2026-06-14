<?php
/** @var array<int, array{name:string,label:string,description:string,enabled:bool,protected:bool,requires:array<int,string>,dependents:array<int,string>}> $modules */
/** @var string $notice */
/** @var string $base */
?>
<h1>Administration</h1>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <h2 style="margin:0 0 .3rem; font-size:1.1rem;">Modules disponibles</h2>
    <p class="muted" style="margin:0 0 1.4rem;">
        Choisissez les modules proposés aux utilisateurs de ce site. Un module désactivé
        disparaît du menu et ses pages publiques ne répondent plus, mais
        <strong>ses données sont conservées</strong> : tout revient à sa réactivation.
    </p>

    <form method="post" action="<?= e($base . '/admin/administration/modules') ?>">
        <?= \App\Core\Csrf::field() ?>

        <ul class="module-list">
            <?php foreach ($modules as $m): ?>
                <li class="module-row<?= $m['enabled'] ? '' : ' off' ?>">
                    <div class="module-meta">
                        <strong><?= e($m['label']) ?></strong>
                        <?php if ($m['description'] !== ''): ?>
                            <span class="muted"><?= e($m['description']) ?></span>
                        <?php endif; ?>
                        <?php if ($m['requires'] !== []): ?>
                            <span class="module-deps">Requiert : <?= e(implode(', ', $m['requires'])) ?></span>
                        <?php elseif ($m['dependents'] !== []): ?>
                            <span class="module-deps">Utilisé par : <?= e(implode(', ', $m['dependents'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($m['protected']): ?>
                        <span class="module-locked" title="Indispensable au fonctionnement de Flow">Toujours actif</span>
                    <?php else: ?>
                        <label class="switch" aria-label="Activer <?= e($m['label']) ?>">
                            <input type="checkbox" name="modules[]" value="<?= e($m['name']) ?>"<?= $m['enabled'] ? ' checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="hint">Si vous désactivez un module dont d'autres dépendent (ex. Médias),
        ces derniers seront désactivés aussi, automatiquement.</p>

        <div class="form-actions">
            <button type="submit">Enregistrer</button>
        </div>
    </form>
</div>

<style>
    .module-list { list-style:none; margin:0; padding:0; }
    .module-row { display:flex; align-items:center; justify-content:space-between; gap:1.2rem;
        padding:.95rem .2rem; border-bottom:1px solid var(--line); }
    .module-row:last-child { border-bottom:0; }
    .module-row.off .module-meta { opacity:.55; }
    .module-meta { display:flex; flex-direction:column; gap:.1rem; }
    .module-meta strong { font-size:.95rem; }
    .module-meta .muted { font-size:.84rem; }
    .module-deps { font-size:.76rem; color:var(--accent-600); }
    .module-locked { flex:0 0 auto; font-size:.76rem; font-weight:600; color:var(--muted);
        background:var(--bg); border:1px solid var(--line); border-radius:999px; padding:.3rem .7rem; }

    /* Interrupteur (checkbox stylée, sans JS). */
    .switch { position:relative; display:inline-block; flex:0 0 auto; width:46px; height:26px; margin:0; }
    .switch input { position:absolute; opacity:0; width:100%; height:100%; margin:0; cursor:pointer; }
    .switch .slider { position:absolute; inset:0; background:#cdd1e2; border-radius:999px;
        transition:background .15s; pointer-events:none; }
    .switch .slider::before { content:""; position:absolute; top:3px; left:3px; width:20px; height:20px;
        background:#fff; border-radius:50%; box-shadow:0 1px 3px rgba(16,24,40,.25); transition:transform .15s; }
    .switch input:checked + .slider { background:var(--accent); }
    .switch input:checked + .slider::before { transform:translateX(20px); }
    .switch input:focus-visible + .slider { box-shadow:0 0 0 4px rgba(43,139,255,.25); }
</style>
