<?php
/** @var array<int, array<string, mixed>> $reviews */
/** @var string|null $lastSync */
/** @var string $notice */
/** @var string $error */
/** @var bool   $canSync */
/** @var bool   $canManage */
/** @var bool   $canConfigure */
/** @var bool   $hasApiKey */
/** @var array{place_id: string, name: string, address: string}|null $place */
/** @var string $q */
/** @var array<int, array{place_id: string, name: string, address: string}> $candidates */
/** @var string $searchError */
/** @var bool   $configured */
/** @var bool   $syncLocked */
/** @var int      $monthlyQuota */
/** @var int      $syncsThisMonth */
/** @var int|null $quotaRemaining */
/** @var bool     $quotaReached */
/** @var string $base */
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Avis Google</h1>
    <?php if ($canSync): ?>
        <div style="text-align:right;">
            <form method="post" action="<?= e($base . '/admin/google-reviews/sync') ?>">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn"<?= ($configured && !$syncLocked && !$quotaReached) ? '' : ' disabled' ?>>
                    Synchroniser les 5 derniers avis
                </button>
            </form>
            <?php if ($quotaReached): ?>
                <p class="muted" style="margin:.4rem 0 0; font-size:.85rem;">
                    Quota mensuel atteint (<?= (int) $monthlyQuota ?>/mois) — réinitialisé le 1er du mois prochain.
                </p>
            <?php elseif ($syncLocked): ?>
                <p class="muted" style="margin:.4rem 0 0; font-size:.85rem;">
                    Une synchronisation par jour — prochaine possible demain.
                </p>
            <?php elseif ($quotaRemaining !== null): ?>
                <p class="muted" style="margin:.4rem 0 0; font-size:.85rem;">
                    <?= (int) $quotaRemaining ?> synchro(s) restante(s) ce mois-ci.
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>

<?php if (!$hasApiKey): ?>
    <div class="notice notice-error">
        Clé API manquante : renseignez <code>GOOGLE_PLACES_API_KEY</code> dans <code>.env.local</code>.
    </div>
<?php endif; ?>

<div class="card" style="margin-top:1.2rem;">
    <h2 style="margin-top:0;">Établissement</h2>

    <?php if ($place !== null): ?>
        <p>
            <strong><?= e($place['name'] !== '' ? $place['name'] : $place['place_id']) ?></strong>
            <?php if ($place['address'] !== ''): ?>
                <span class="muted">— <?= e($place['address']) ?></span>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p class="muted">Aucun établissement choisi pour l'instant.</p>
    <?php endif; ?>

    <?php if ($canConfigure && !$hasApiKey): ?>
        <p class="muted">
            La recherche d'établissement sera disponible une fois la clé
            <code>GOOGLE_PLACES_API_KEY</code> renseignée dans <code>.env.local</code>.
        </p>
    <?php endif; ?>

    <?php if ($canConfigure && $hasApiKey): ?>
        <form method="get" action="<?= e($base . '/admin/google-reviews') ?>" class="actions" style="margin-top:.6rem;">
            <input type="text" name="q" value="<?= e($q) ?>"
                   placeholder="Nom de l'établissement (ex. Boulangerie Dupont Carcassonne)"
                   style="flex:1; min-width:18rem;">
            <button type="submit" class="btn-secondary">Rechercher l'établissement</button>
        </form>

        <?php if ($searchError !== ''): ?>
            <div class="notice notice-error" style="margin-top:.8rem;"><?= e($searchError) ?></div>
        <?php endif; ?>

        <?php if ($q !== '' && $searchError === ''): ?>
            <?php if ($candidates === []): ?>
                <p class="muted" style="margin-top:.8rem;">
                    Aucun résultat pour « <?= e($q) ?> ». Précisez le nom (ajoutez la ville).
                </p>
            <?php else: ?>
                <table style="margin-top:.8rem;">
                    <thead><tr><th>Établissement</th><th>Adresse</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($candidates as $c): ?>
                        <tr>
                            <td><?= e($c['name']) ?></td>
                            <td class="muted"><?= e($c['address']) ?></td>
                            <td>
                                <form method="post" action="<?= e($base . '/admin/google-reviews/place') ?>">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="place_id" value="<?= e($c['place_id']) ?>">
                                    <input type="hidden" name="name" value="<?= e($c['name']) ?>">
                                    <input type="hidden" name="address" value="<?= e($c['address']) ?>">
                                    <button type="submit" class="btn-secondary">Choisir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($canConfigure): ?>
    <div class="card" style="margin-top:1.2rem;">
        <h2 style="margin-top:0;">Quota mensuel de synchronisation</h2>
        <p class="muted">
            Plafonne le nombre de synchronisations Google par mois (maîtrise du coût d'API).
            Le compteur se remet à zéro le 1er de chaque mois. Mettez <code>0</code> pour des
            synchronisations illimitées.
        </p>
        <form method="post" action="<?= e($base . '/admin/google-reviews/quota') ?>"
              class="actions" style="margin-top:.6rem; align-items:center;">
            <?= \App\Core\Csrf::field() ?>
            <label for="monthly_quota">Synchros par mois</label>
            <input type="number" id="monthly_quota" name="monthly_quota" min="0" step="1"
                   value="<?= (int) $monthlyQuota ?>" style="width:6rem;">
            <button type="submit" class="btn-secondary">Enregistrer</button>
        </form>
        <p class="muted" style="margin:.6rem 0 0;">
            <?php if ($quotaRemaining === null): ?>
                Synchronisations illimitées — <?= (int) $syncsThisMonth ?> effectuée(s) ce mois-ci.
            <?php else: ?>
                <strong><?= (int) $syncsThisMonth ?> / <?= (int) $monthlyQuota ?></strong>
                utilisée(s) ce mois-ci — <?= (int) $quotaRemaining ?> restante(s).
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<div class="actions" style="justify-content:space-between; margin-top:1.2rem;">
    <p class="muted" style="margin:0;">
        <?= $lastSync !== null ? 'Dernière synchronisation : ' . e($lastSync) : '' ?>
    </p>
    <?php if ($canManage && $reviews !== []): ?>
        <form method="post" action="<?= e($base . '/admin/google-reviews/purge') ?>"
              onsubmit="return confirm('Vider la table ? Tous les avis importés (et leur curation) seront supprimés.');">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn-danger">Vider la table</button>
        </form>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:1.2rem;">
    <table>
        <thead>
            <tr><th>Affiché</th><th>Auteur</th><th>Note</th><th>Commentaire</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ($reviews === []): ?>
            <tr><td colspan="6">Aucun avis pour l'instant. Lancez une première synchronisation.</td></tr>
        <?php endif; ?>
        <?php foreach ($reviews as $r): ?>
            <?php
                $visible = (int) $r['is_visible'] === 1;
                $rating  = max(0, min(5, (int) $r['rating']));
                $stars   = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $text    = trim((string) ($r['text'] ?? ''));
            ?>
            <tr class="<?= $visible ? '' : 'gr-hidden' ?>">
                <td>
                    <span class="status <?= $visible ? 'status-published' : 'status-draft' ?>">
                        <?= $visible ? 'Affiché' : 'Masqué' ?>
                    </span>
                </td>
                <td><?= e($r['author_name']) ?></td>
                <td title="<?= $rating ?>/5"><?= $stars ?></td>
                <td class="gr-comment">
                    <?= $text !== '' ? e($text) : '<em class="muted">— aucun commentaire —</em>' ?>
                </td>
                <td class="muted"><?= e($r['relative_time']) ?></td>
                <td>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= e($base . '/admin/google-reviews/toggle') ?>">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <input type="hidden" name="visible" value="<?= $visible ? '0' : '1' ?>">
                            <button type="submit" class="btn-secondary">
                                <?= $visible ? 'Masquer' : 'Afficher' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .notice { background:#e7f6ec; border:1px solid #a8d5b9; color:#1c6b3a; padding:.7rem .9rem; border-radius:7px; margin-bottom:1rem; }
    .notice-error { background:#fdecec; border-color:#f1c4c4; color:#c0322f; }
    .muted { color:var(--muted); }
    .status { font-size:.8rem; padding:.15rem .5rem; border-radius:5px; }
    .status-published { background:#e7f6ec; color:#1c6b3a; }
    .status-draft { background:#fdf0d5; color:#8a5a00; }
    .gr-hidden td { opacity:.55; }
    .gr-comment { max-width:34rem; }
    td .btn-secondary, .card .btn-secondary { display:inline-flex; align-items:center; line-height:1.2;
        padding:.5rem .9rem; font-size:.85rem; border-radius:9px; border:1px solid; cursor:pointer; }
    .btn-danger { display:inline-flex; align-items:center; line-height:1.2; padding:.5rem .9rem;
        font-size:.85rem; border-radius:9px; border:1px solid #f1c4c4; background:#fff; color:#c0322f; cursor:pointer; }
    .btn-danger:hover { background:#fdecec; }
    .btn[disabled] { opacity:.5; cursor:not-allowed; }
</style>
