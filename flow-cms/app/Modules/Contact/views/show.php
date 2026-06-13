<?php
/** @var \App\Modules\Contact\ContactSubmission $item */
/** @var string $consentText */
/** @var string $base */

$fmt = static function (string $dt): string {
    $d = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dt);
    return $d !== false ? $d->format('d/m/Y à H:i') : $dt;
};
?>
<div class="actions" style="justify-content:space-between;">
    <h1 style="margin:0;">Demande de contact</h1>
    <a class="btn-secondary" href="<?= e($base . '/admin/contact') ?>">← Retour</a>
</div>

<div class="card" style="margin-top:1.2rem;max-width:720px;">
    <dl class="contact-detail">
        <dt>Nom</dt><dd><?= e($item->name) ?></dd>
        <dt>E-mail</dt><dd><a href="mailto:<?= e($item->email) ?>"><?= e($item->email) ?></a></dd>
        <dt>Sujet</dt><dd><?= e($item->subject !== '' ? $item->subject : '—') ?></dd>
        <dt>Reçue le</dt><dd><?= e($fmt($item->createdAt)) ?></dd>
        <dt>Message</dt><dd class="message"><?= nl2br(e($item->message)) ?></dd>
        <dt>Consentement RGPD</dt>
        <dd>
            <?= $item->consent ? '✓ accepté' : '✗ non' ?>
            <?php if ($item->consentVersion !== ''): ?>
                <span class="muted">(version <?= e($item->consentVersion) ?>)</span>
            <?php endif; ?>
            <?php if ($consentText !== ''): ?>
                <div class="muted" style="font-size:.85rem;margin-top:.3rem;">« <?= e($consentText) ?> »</div>
            <?php endif; ?>
        </dd>
    </dl>

    <div class="actions" style="margin-top:1.4rem;gap:.6rem;border-top:1px solid #eee;padding-top:1rem;">
        <a class="btn" href="mailto:<?= e($item->email) ?>?subject=<?= rawurlencode('Re: ' . $item->subject) ?>">Répondre par e-mail</a>

        <form method="post" action="<?= e($base . '/admin/contact/status') ?>" style="display:inline">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= e($item->id) ?>">
            <input type="hidden" name="status" value="archived">
            <button type="submit" class="btn-secondary">Archiver</button>
        </form>

        <form method="post" action="<?= e($base . '/admin/contact/delete') ?>"
              onsubmit="return confirm('Supprimer définitivement cette demande ?');" style="display:inline">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= e($item->id) ?>">
            <button type="submit" class="btn-danger">Supprimer</button>
        </form>
    </div>
</div>

<style>
    .muted { color:var(--muted); }
    .contact-detail { display:grid; grid-template-columns:160px 1fr; gap:.6rem 1rem; margin:0; }
    .contact-detail dt { font-weight:600; color:var(--muted); }
    .contact-detail dd { margin:0; }
    .contact-detail dd.message { white-space:normal; line-height:1.6; }
    .btn-secondary { display:inline-flex; align-items:center; line-height:1.2; padding:.5rem .9rem;
        font-size:.85rem; border-radius:9px; border:1px solid; text-decoration:none; }
    .btn-danger { background:#fff; border:1px solid #f1c4c4; color:#c0322f; cursor:pointer;
        padding:.5rem .9rem; font-size:.85rem; border-radius:9px; }
    .btn-danger:hover { background:#fdecec; }
</style>
