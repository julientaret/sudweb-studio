<?php
/**
 * Constructeur du formulaire de devis : étapes et questions du pop-up public.
 *
 * Le schéma courant est injecté en JSON dans un champ caché ; le constructeur
 * (assets/js/devis-form.js) rend une interface d'édition et resynchronise ce
 * champ avant l'envoi. Sans JavaScript, on affiche un repli explicite — l'édition
 * du formulaire reste une fonctionnalité avancée, réservée à l'admin.
 *
 * @var string $schemaJson  schéma courant {steps:[...]} en JSON
 * @var array<int, string> $types  types de question autorisés
 * @var string $notice
 * @var string $base
 */
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Formulaire de devis</h1>
    <a class="btn-secondary" href="<?= e($base . '/admin/devis') ?>">← Retour aux demandes</a>
</div>

<?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

<p class="muted" style="margin:.6rem 0 1.2rem;max-width:760px;">
    Composez le pop-up de demande de devis : ajoutez des <strong>étapes</strong>, et dans chaque étape
    des <strong>questions</strong> (texte, e-mail, téléphone, zone de texte ou liste de choix).
    Les questions <em>Nom</em> et <em>E-mail</em> sont indispensables et ne peuvent pas être supprimées.
</p>

<form method="post" action="<?= e($base . '/admin/devis/form') ?>" data-devis-builder>
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="schema" id="devis-schema" value="<?= e($schemaJson) ?>"
           data-types="<?= e(implode(',', $types)) ?>">

    <noscript>
        <div class="alert">L’édition du formulaire nécessite JavaScript. Activez-le pour utiliser le constructeur.</div>
    </noscript>

    <div data-builder-root>
        <p class="muted">Chargement du constructeur…</p>
    </div>

    <div class="form-actions">
        <button type="submit">Enregistrer le formulaire</button>
        <a class="btn-secondary" href="<?= e($base . '/admin/devis') ?>">Annuler</a>
    </div>
</form>

<style>
    .notice { background:#e7f6ec; border:1px solid #a8d5b9; color:#1c6b3a; padding:.7rem .9rem; border-radius:7px; margin-bottom:1rem; }
    .muted { color:var(--muted); }

    .db-steps-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.1rem; margin-bottom:1.1rem; }
    @media (max-width:860px){ .db-steps-grid { grid-template-columns:1fr; } }
    .db-step { background:var(--panel); border:1px solid var(--line); border-radius:14px;
        padding:1.1rem 1.2rem; box-shadow:var(--shadow-sm); }
    .db-step-head { display:flex; align-items:center; gap:.7rem; margin-bottom:.9rem; }
    .db-step-head .db-num { width:26px; height:26px; flex:0 0 auto; border-radius:8px;
        background:var(--accent-soft); color:var(--accent-600); font-weight:700; font-size:.85rem;
        display:grid; place-items:center; }
    .db-step-head input { font-weight:600; }
    .db-step-head .db-tools { margin-left:auto; display:flex; gap:.35rem; }

    .db-field { border:1px solid var(--line); border-radius:11px; padding:.8rem .9rem; margin-bottom:.7rem;
        background:#fbfbff; }
    .db-field-row { display:grid; grid-template-columns:1fr 150px 110px auto; gap:.6rem; align-items:end; }
    @media (max-width:680px){ .db-field-row { grid-template-columns:1fr 1fr; } }
    .db-field label { margin:0 0 .25rem; font-size:.76rem; }
    .db-field .db-opts { margin-top:.6rem; }
    .db-field .db-opts label { display:block; }
    .db-field textarea { min-height:60px; }
    .db-core-tag { display:inline-block; font-size:.72rem; font-weight:600; color:var(--accent-600);
        background:var(--accent-soft); padding:.1rem .45rem; border-radius:5px; margin-left:.4rem; }
    .db-req { display:flex; align-items:center; gap:.35rem; font-size:.8rem; white-space:nowrap; }
    .db-req input { width:auto; }

    .db-mini { padding:.45rem .7rem; font-size:.8rem; border-radius:8px; box-shadow:none;
        background:var(--accent-soft); color:var(--accent-600); }
    .db-mini:hover { background:#e2e5ff; filter:none; box-shadow:none; }
    .db-mini.db-danger { background:#fff; border:1px solid #f1c4c4; color:#c0322f; }
    .db-mini.db-danger:hover { background:#fdecec; }
    .db-mini[disabled] { opacity:.4; cursor:default; }
    .db-add-field { margin-top:.3rem; }
    .db-add-step { width:100%; padding:.8rem; background:#fff; border:1.5px dashed #cfd3e6;
        color:var(--accent-600); box-shadow:none; font-weight:600; }
    .db-add-step:hover { border-color:var(--accent); background:#f6f7ff; filter:none; box-shadow:none; }
</style>

<script src="<?= e($base . '/assets/js/devis-form.js') ?>"></script>
