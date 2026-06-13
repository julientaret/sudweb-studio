<?php
/**
 * Tableau de bord d'audience. Tout est rendu server-side ; le graphe est en
 * CSS pur (barres), zéro JavaScript ni librairie tierce — cohérent avec la
 * philosophie « zéro dépendance runtime » et privacy-first du projet.
 */
/** @var int $days */
/** @var array<int, int> $periods */
/** @var int $totalViews */
/** @var int $uniqueVisitors */
/** @var array<int, array{date: string, label: string, views: int, uniques: int}> $daily */
/** @var array<int, array{date: string, label: string, views: int, uniques: int}> $month */
/** @var string $monthLabel */
/** @var array<int, array{path: string, views: int}> $topPages */
/** @var array<int, array{host: string, views: int}> $topReferers */
/** @var array<string, int> $devices */
/** @var int $retention */
/** @var string $base */

$periodLabels = [7 => '7 jours', 30 => '30 jours', 90 => '90 jours', 365 => '12 mois'];
$deviceLabels = ['desktop' => 'Ordinateur', 'mobile' => 'Mobile', 'tablet' => 'Tablette', 'other' => 'Autre'];

$maxViews = 0;
foreach ($daily as $d) {
    if ($d['views'] > $maxViews) {
        $maxViews = $d['views'];
    }
}
$maxMonth = 0;
$monthViews = 0;
$monthUniques = 0;
foreach ($month as $d) {
    $maxMonth = max($maxMonth, $d['views']);
    $monthViews += $d['views'];
    $monthUniques += $d['uniques'];
}
$deviceTotal = array_sum($devices);
?>
<div class="actions" style="justify-content:space-between;align-items:center;">
    <h1 style="margin:0;">Statistiques de visite</h1>
    <div class="period-switch">
        <?php foreach ($periods as $p): ?>
            <a href="<?= e($base . '/admin/stats?days=' . $p) ?>"
               class="period-btn<?= $p === $days ? ' is-active' : '' ?>"><?= e($periodLabels[$p] ?? ($p . ' j')) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="kpis">
    <div class="card kpi">
        <span class="kpi-label">Pages vues</span>
        <span class="kpi-value"><?= e(number_format($totalViews, 0, ',', ' ')) ?></span>
        <span class="muted kpi-sub">sur <?= e($periodLabels[$days] ?? ($days . ' jours')) ?></span>
    </div>
    <div class="card kpi">
        <span class="kpi-label">Visiteurs uniques</span>
        <span class="kpi-value"><?= e(number_format($uniqueVisitors, 0, ',', ' ')) ?></span>
        <span class="muted kpi-sub">cumul des uniques/jour</span>
    </div>
</div>

<div class="card month-card" style="margin-top:1.2rem;">
    <div class="month-head">
        <h2 class="block-title" style="margin:0;">Mois en cours — <?= e($monthLabel) ?></h2>
        <span class="muted month-sub"><?= e(number_format($monthViews, 0, ',', ' ')) ?> vues ·
            <?= e(number_format($monthUniques, 0, ',', ' ')) ?> visiteurs uniques</span>
    </div>
    <?php if ($maxMonth === 0): ?>
        <p class="muted" style="margin-top:.8rem;">Aucune visite enregistrée ce mois-ci.</p>
    <?php else: ?>
        <div class="chart chart-sm">
            <?php foreach ($month as $d): ?>
                <?php $h = $d['views'] > 0 ? max(3, (int) round($d['views'] / $maxMonth * 100)) : 0; ?>
                <div class="chart-col" title="<?= e($d['date'] . ' — ' . $d['views'] . ' vues, ' . $d['uniques'] . ' visiteurs uniques') ?>">
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height:<?= e((string) $h) ?>%;"></div>
                    </div>
                    <span class="chart-label chart-label-day"><?= e($d['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="muted chart-legend">Une barre par jour écoulé du mois (max : <?= e((string) $maxMonth) ?> vues/jour).</p>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:1.2rem;">
    <h2 class="block-title">Fréquentation par jour</h2>
    <?php if ($maxViews === 0): ?>
        <p class="muted">Aucune visite enregistrée sur la période.</p>
    <?php else: ?>
        <div class="chart">
            <?php foreach ($daily as $d): ?>
                <?php $h = $d['views'] > 0 ? max(2, (int) round($d['views'] / $maxViews * 100)) : 0; ?>
                <div class="chart-col" title="<?= e($d['date'] . ' — ' . $d['views'] . ' vues, ' . $d['uniques'] . ' visiteurs uniques') ?>">
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height:<?= e((string) $h) ?>%;"></div>
                    </div>
                    <span class="chart-label"><?= e($d['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="muted chart-legend">Hauteur = pages vues par jour (max : <?= e((string) $maxViews) ?>).</p>
    <?php endif; ?>
</div>

<div class="cols">
    <div class="card">
        <h2 class="block-title">Pages les plus vues</h2>
        <table>
            <thead><tr><th>Page</th><th class="num">Vues</th></tr></thead>
            <tbody>
            <?php if ($topPages === []): ?>
                <tr><td colspan="2" class="muted">Aucune donnée.</td></tr>
            <?php endif; ?>
            <?php foreach ($topPages as $row): ?>
                <tr>
                    <td><code><?= e($row['path']) ?></code></td>
                    <td class="num"><?= e(number_format($row['views'], 0, ',', ' ')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 class="block-title">Sources de trafic</h2>
        <table>
            <thead><tr><th>Référent</th><th class="num">Vues</th></tr></thead>
            <tbody>
            <?php if ($topReferers === []): ?>
                <tr><td colspan="2" class="muted">Aucun référent externe (accès directs).</td></tr>
            <?php endif; ?>
            <?php foreach ($topReferers as $row): ?>
                <tr>
                    <td><?= e($row['host']) ?></td>
                    <td class="num"><?= e(number_format($row['views'], 0, ',', ' ')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:1.2rem;">
    <h2 class="block-title">Appareils</h2>
    <?php if ($deviceTotal === 0): ?>
        <p class="muted">Aucune donnée.</p>
    <?php else: ?>
        <div class="devices">
            <?php foreach ($devices as $device => $views): ?>
                <?php $pct = (int) round($views / $deviceTotal * 100); ?>
                <div class="device-row">
                    <span class="device-name"><?= e($deviceLabels[$device] ?? $device) ?></span>
                    <span class="device-track"><span class="device-fill" style="width:<?= e((string) $pct) ?>%;"></span></span>
                    <span class="device-pct muted"><?= e((string) $pct) ?> % (<?= e(number_format($views, 0, ',', ' ')) ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<p class="muted" style="margin-top:1rem;font-size:.85rem;">
    RGPD : mesure d'audience sans cookie ni service tiers. L'IP et le navigateur
    ne sont jamais conservés en clair (empreinte non réversible à sel quotidien) ;
    les robots et les membres connectés sont exclus. Les données sont purgées
    automatiquement au-delà de <?= e((string) $retention) ?> mois (voir <code>config/stats.php</code>).
</p>

<style>
    .period-switch { display:flex; gap:.4rem; }
    .period-btn { padding:.4rem .8rem; font-size:.85rem; border-radius:8px;
        border:1px solid var(--line); color:var(--muted); text-decoration:none; }
    .period-btn.is-active { background:var(--accent-soft); border-color:var(--accent);
        color:var(--accent-600); font-weight:600; }
    .kpis { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin-top:1.2rem; }
    .kpi { display:flex; flex-direction:column; gap:.25rem; }
    .kpi-label { font-size:.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; }
    .kpi-value { font-size:2rem; font-weight:700; line-height:1.1; }
    .kpi-sub { font-size:.8rem; }
    .muted { color:var(--muted); }
    .block-title { font-size:1rem; margin:0 0 1rem; }
    .num { text-align:right; white-space:nowrap; }
    .cols { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1.2rem; }
    .chart { display:flex; align-items:flex-end; gap:2px; height:180px; overflow-x:auto; padding-top:.5rem; }
    .chart-col { flex:1 0 14px; display:flex; flex-direction:column; align-items:center; gap:.3rem; min-width:14px; }
    .chart-bar-wrap { display:flex; align-items:flex-end; height:150px; width:100%; }
    .chart-bar { width:100%; background:var(--grad); border-radius:4px 4px 0 0; min-height:2px; transition:height .2s; }
    .chart-label { font-size:.62rem; color:var(--muted); white-space:nowrap; transform:rotate(-45deg); transform-origin:center; }
    .chart-legend { margin-top:1.4rem; font-size:.8rem; }
    .month-head { display:flex; justify-content:space-between; align-items:baseline; gap:1rem; flex-wrap:wrap; }
    .month-sub { font-size:.82rem; }
    .chart-sm { height:130px; margin-top:.8rem; gap:3px; }
    .chart-sm .chart-bar-wrap { height:104px; }
    .chart-label-day { font-size:.6rem; transform:none; }
    .devices { display:flex; flex-direction:column; gap:.6rem; }
    .device-row { display:grid; grid-template-columns:90px 1fr 110px; align-items:center; gap:.6rem; }
    .device-name { font-size:.85rem; }
    .device-track { height:10px; background:var(--line); border-radius:6px; overflow:hidden; }
    .device-fill { display:block; height:100%; background:var(--grad); border-radius:6px; }
    .device-pct { font-size:.8rem; text-align:right; }
    @media (max-width:720px) { .cols, .kpis { grid-template-columns:1fr; } }
</style>
