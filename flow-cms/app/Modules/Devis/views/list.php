<?php
/** @var array<int, \App\Modules\Devis\QuoteRequest> $items */
/** @var string $notice */
/** @var bool $canConfigure */
/** @var string $base */

$fmt = static function (string $dt): string {
    $d = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dt);
    return $d !== false ? $d->format('d/m/Y H:i') : $dt;
};
$labels = ['new' => 'Nouvelle', 'read' => 'Lue', 'quoted' => 'Devis envoyé', 'archived' => 'Archivée'];
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Demandes de devis</h1>
    <?php if ($canConfigure ?? false): ?>
    <a class="btn" href="<?= e($base . '/admin/devis/form') ?>">Configurer le formulaire</a>
    <?php endif; ?>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<div class="card" style="margin-top:1.2rem;">
    <table>
        <thead>
            <tr><th>Statut</th><th>Nom</th><th>E-mail</th><th>Aperçu</th><th>Reçue le</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ($items === []): ?>
            <tr><td colspan="6">Aucune demande pour l’instant.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $it): ?>
            <tr<?= $it->isNew() ? ' style="font-weight:600;"' : '' ?>>
                <td><span class="status status-<?= e($it->status) ?>"><?= e($labels[$it->status] ?? $it->status) ?></span></td>
                <td><?= e($it->name) ?></td>
                <td class="muted"><a href="mailto:<?= e($it->email) ?>"><?= e($it->email) ?></a></td>
                <td class="muted"><?= e($it->summary() !== '' ? $it->summary() : '—') ?></td>
                <td class="muted"><?= e($fmt($it->createdAt)) ?></td>
                <td>
                    <a class="btn-secondary" href="<?= e($base . '/admin/devis/show?id=' . $it->id) ?>">Voir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="muted" style="margin-top:1rem;font-size:.85rem;">
    RGPD : les demandes sont automatiquement purgées au-delà du délai de conservation
    configuré (voir <code>config/devis.php</code>).
</p>

<style>
    .notice { background:#e7f6ec; border:1px solid #a8d5b9; color:#1c6b3a; padding:.7rem .9rem; border-radius:7px; margin-bottom:1rem; }
    .muted { color:var(--muted); }
    .status { font-size:.8rem; padding:.15rem .5rem; border-radius:5px; }
    .status-new { background:#fdf0d5; color:#8a5a00; }
    .status-read { background:#e7f6ec; color:#1c6b3a; }
    .status-quoted { background:#e8f2ff; color:#1f6fe0; }
    .status-archived { background:#eee; color:#555; }
    td .btn-secondary { display:inline-flex; align-items:center; line-height:1.2;
        padding:.5rem .9rem; font-size:.85rem; border-radius:9px; border:1px solid; }
</style>
