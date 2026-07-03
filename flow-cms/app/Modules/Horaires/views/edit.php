<?php
/** @var array<string, array{closed: bool, open: string, close: string}> $weekly */
/** @var array<string, string> $dayLabels */
/** @var array<int, string> $days */
/** @var array<int, array{start: string, end: string, label: string}> $exceptions */
/** @var bool $canManage */
/** @var string $notice */
/** @var string $base */
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Horaires d'ouverture</h1>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<div class="card" style="margin-top:1.2rem;">
    <h2 style="margin-top:0;">Horaires hebdomadaires</h2>
    <p class="muted">Réglez les horaires pour chaque jour, ou marquez-le comme fermé.</p>

    <form method="post" action="<?= e($base . '/admin/horaires/weekly') ?>" style="margin-top:1rem;">
        <?= \App\Core\Csrf::field() ?>
        <table>
            <thead>
                <tr><th>Jour</th><th>Fermé</th><th>Ouverture</th><th>Fermeture</th></tr>
            </thead>
            <tbody>
            <?php foreach ($days as $day): ?>
                <?php $row = $weekly[$day]; ?>
                <tr class="oh-day-row" data-day="<?= e($day) ?>">
                    <td><strong><?= e($dayLabels[$day]) ?></strong></td>
                    <td>
                        <input type="checkbox" class="oh-closed" name="days[<?= e($day) ?>][closed]" value="1"
                               <?= $row['closed'] ? 'checked' : '' ?> <?= $canManage ? '' : 'disabled' ?>>
                    </td>
                    <td>
                        <input type="time" name="days[<?= e($day) ?>][open]" value="<?= e($row['open']) ?>"
                               <?= $row['closed'] ? 'disabled' : '' ?> <?= $canManage ? '' : 'disabled' ?>>
                    </td>
                    <td>
                        <input type="time" name="days[<?= e($day) ?>][close]" value="<?= e($row['close']) ?>"
                               <?= $row['closed'] ? 'disabled' : '' ?> <?= $canManage ? '' : 'disabled' ?>>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($canManage): ?>
            <div class="form-actions">
                <button type="submit" class="btn">Enregistrer les horaires</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="margin-top:1.2rem;">
    <h2 style="margin-top:0;">Fermetures exceptionnelles</h2>
    <p class="muted">Congés, jours fériés ou toute autre fermeture ponctuelle — affichées sur le site tant qu'elles ne sont pas passées.</p>

    <form method="post" action="<?= e($base . '/admin/horaires/exceptions') ?>" id="oh-exceptions-form" style="margin-top:1rem;">
        <?= \App\Core\Csrf::field() ?>
        <div id="oh-exceptions-rows">
            <?php foreach ($exceptions as $i => $ex): ?>
                <div class="oh-exception-row">
                    <div class="field">
                        <label>Du</label>
                        <input type="date" name="exceptions[<?= $i ?>][start]" value="<?= e($ex['start']) ?>" <?= $canManage ? '' : 'disabled' ?>>
                    </div>
                    <div class="field">
                        <label>Au</label>
                        <input type="date" name="exceptions[<?= $i ?>][end]" value="<?= e($ex['end']) ?>" <?= $canManage ? '' : 'disabled' ?>>
                    </div>
                    <div class="field oh-exception-label">
                        <label>Motif</label>
                        <input type="text" name="exceptions[<?= $i ?>][label]" value="<?= e($ex['label']) ?>"
                               placeholder="Congés d'été, Noël…" <?= $canManage ? '' : 'disabled' ?>>
                    </div>
                    <?php if ($canManage): ?>
                        <div class="field oh-exception-remove-field">
                            <label>&nbsp;</label>
                            <button type="button" class="btn-danger oh-exception-remove" title="Supprimer">&times;</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($canManage): ?>
            <template id="oh-tpl-exception">
                <div class="oh-exception-row">
                    <div class="field">
                        <label>Du</label>
                        <input type="date" name="exceptions[__OKEY__][start]">
                    </div>
                    <div class="field">
                        <label>Au</label>
                        <input type="date" name="exceptions[__OKEY__][end]">
                    </div>
                    <div class="field oh-exception-label">
                        <label>Motif</label>
                        <input type="text" name="exceptions[__OKEY__][label]" placeholder="Congés d'été, Noël…">
                    </div>
                    <div class="field oh-exception-remove-field">
                        <label>&nbsp;</label>
                        <button type="button" class="btn-danger oh-exception-remove" title="Supprimer">&times;</button>
                    </div>
                </div>
            </template>

            <div class="form-actions">
                <button type="button" id="oh-add-exception" class="btn-secondary">+ Ajouter une fermeture</button>
                <button type="submit" class="btn">Enregistrer les fermetures</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<style>
    .muted { color:var(--muted); }
    .oh-day-row td { vertical-align:middle; }
    .oh-day-row input[type=time] { width:auto; }
    .oh-day-row input[disabled] { opacity:.45; }
    #oh-exceptions-rows { display:flex; flex-direction:column; gap:.8rem; }
    /* stretch (pas flex-end) : un <input type=date> est nativement plus haut
       qu'un bouton à hauteur fixe — on aligne tout sur la hauteur du plus grand
       plutôt que de deviner une hauteur commune. */
    .oh-exception-row { display:flex; gap:1rem; align-items:stretch; padding-bottom:.8rem; border-bottom:1px solid var(--line); }
    .oh-exception-row .field { margin:0; display:flex; flex-direction:column; }
    .oh-exception-row .field label { flex:0 0 auto; }
    .oh-exception-label { flex:1; }
    .oh-exception-row input[type=date] { width:auto; }
    .oh-exception-remove-field { flex:0 0 auto; }
    .oh-exception-remove { flex:1 1 auto; width:2.6rem; padding:0; font-size:1.2rem; line-height:1;
        display:inline-flex; align-items:center; justify-content:center; }
    a.btn-secondary, button.btn-secondary { display:inline-flex; align-items:center; gap:.4rem; padding:.7rem 1.1rem;
        border-radius:11px; border:1px solid var(--line); background:#fff; color:var(--txt); font:inherit;
        font-weight:600; cursor:pointer; transition:.15s; box-sizing:border-box; }
    button.btn-secondary:hover { border-color:var(--accent); color:var(--accent-600); }
    /* Même hauteur que le bouton "Enregistrer" malgré la bordure en plus. */
    #oh-exceptions-form .form-actions { align-items:stretch; }
    #oh-exceptions-form .form-actions .btn,
    #oh-exceptions-form .form-actions .btn-secondary { box-sizing:border-box; }
</style>

<?php if ($canManage): ?>
<script src="<?= e($base . '/assets/js/horaires-editor.js') ?>" defer></script>
<?php endif; ?>
