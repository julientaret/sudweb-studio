<?php
/** @var bool $enabled */
/** @var string $bypassUrl */
/** @var array<int, string> $ips */
/** @var string $clientIp */
/** @var string $notice */
/** @var string $error */
/** @var string $base */
?>
<h1>Maintenance</h1>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<div class="card" style=" margin-bottom:1.4rem;">
    <div class="mtn-head">
        <div>
            <h2 style="margin:0 0 .3rem; font-size:1.1rem;">Mode maintenance</h2>
            <p class="muted" style="margin:0;">
                Quand il est actif, tout le site public est remplacé par une page de maintenance
                (erreur 503). L'administration, elle, reste toujours accessible.
            </p>
        </div>
        <span class="mtn-state <?= $enabled ? 'on' : 'off' ?>">
            <span class="dot"></span><?= $enabled ? 'Site fermé' : 'Site en ligne' ?>
        </span>
    </div>

    <form method="post" action="<?= e($base . '/admin/maintenance/toggle') ?>" style="margin-top:1.2rem;">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="enabled" value="<?= $enabled ? '0' : '1' ?>">
        <button type="submit" class="<?= $enabled ? 'secondary' : '' ?>">
            <?= $enabled ? 'Désactiver la maintenance' : 'Activer la maintenance' ?>
        </button>
    </form>
</div>

<div class="card" style=" margin-bottom:1.4rem;">
    <h2 style="margin:0 0 .3rem; font-size:1.1rem;">URL de contournement (bypass)</h2>
    <p class="muted" style="margin:0 0 1rem;">
        Partagez cette URL secrète : l'ouvrir autorise le visiteur à voir le site malgré la
        maintenance, et <strong>ajoute automatiquement son adresse IP</strong> à la liste ci-dessous.
    </p>

    <div class="mtn-url">
        <input type="text" readonly value="<?= e($bypassUrl) ?>" onclick="this.select()" aria-label="URL de bypass">
    </div>

    <form method="post" action="<?= e($base . '/admin/maintenance/regenerate') ?>" style="margin-top:1rem;"
          onsubmit="return confirm('Régénérer le jeton ? L\'ancienne URL de bypass cessera de fonctionner.');">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="ghost">Régénérer le jeton</button>
    </form>
</div>

<div class="card" style="">
    <h2 style="margin:0 0 .3rem; font-size:1.1rem;">Adresses IP autorisées</h2>
    <p class="muted" style="margin:0 0 1rem;">
        Ces adresses contournent la maintenance sans jeton. Votre IP actuelle est
        <code><?= e($clientIp) ?></code>.
    </p>

    <form method="post" action="<?= e($base . '/admin/maintenance/ip/add') ?>" class="mtn-add">
        <?= \App\Core\Csrf::field() ?>
        <input type="text" name="ip" placeholder="Ex. 203.0.113.4" value="<?= e($clientIp) ?>" aria-label="Adresse IP à ajouter">
        <button type="submit">Ajouter</button>
    </form>

    <?php if ($ips === []): ?>
        <p class="muted" style="margin:1.2rem 0 0;">Aucune adresse IP autorisée pour le moment.</p>
    <?php else: ?>
        <ul class="mtn-ips">
            <?php foreach ($ips as $ip): ?>
                <li>
                    <code><?= e($ip) ?></code>
                    <form method="post" action="<?= e($base . '/admin/maintenance/ip/remove') ?>">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="ip" value="<?= e($ip) ?>">
                        <button type="submit" class="link" title="Retirer cette adresse">Retirer</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<style>
    .mtn-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1.2rem; }
    .mtn-state { flex:0 0 auto; display:inline-flex; align-items:center; gap:.5rem; font-size:.8rem;
        font-weight:600; border-radius:999px; padding:.35rem .8rem; border:1px solid var(--line); white-space:nowrap; }
    .mtn-state .dot { width:8px; height:8px; border-radius:50%; flex:0 0 auto; }
    .mtn-state.on { color:#a3211f; background:#fdecec; border-color:#f6bcbc; }
    .mtn-state.on .dot { background:#e0457b; box-shadow:0 0 0 3px rgba(224,69,123,.18); }
    .mtn-state.off { color:#15724a; background:#e7f8ee; border-color:#a9e0c0; }
    .mtn-state.off .dot { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.18); }

    .mtn-url input { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.85rem; background:#fbfbff; }

    .mtn-add { display:flex; gap:.7rem; align-items:stretch; }
    .mtn-add input { flex:1; }
    .mtn-add button { flex:0 0 auto; }

    .mtn-ips { list-style:none; margin:1.2rem 0 0; padding:0; }
    .mtn-ips li { display:flex; align-items:center; justify-content:space-between; gap:1rem;
        padding:.7rem .2rem; border-bottom:1px solid var(--line); }
    .mtn-ips li:last-child { border-bottom:0; }
    .mtn-ips code { font-size:.9rem; }
    .mtn-ips form { margin:0; }
</style>
