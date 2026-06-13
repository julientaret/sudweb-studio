<?php
/**
 * E-mail HTML envoyé au propriétaire du site à chaque nouvelle demande.
 *
 * Mis en forme par CSS *inline* : les clients mail (Gmail, Outlook…) ignorent
 * largement les feuilles externes et beaucoup de sélecteurs. Tout est échappé
 * avec e().
 *
 * @var string $siteName
 * @var string $name
 * @var string $email
 * @var string $subject
 * @var string $message
 * @var string $date
 * @var string $adminUrl  URL absolue du site (peut être vide)
 */
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2430;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:92%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">

                <!-- En-tête -->
                <tr><td style="background:#1f2430;padding:26px 32px;">
                    <div style="color:#9aa3b2;font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;"><?= e($siteName) ?></div>
                    <div style="color:#ffffff;font-size:21px;font-weight:700;">📨 Nouvelle demande de contact</div>
                </td></tr>

                <!-- Corps -->
                <tr><td style="padding:28px 32px;">
                    <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#444c5a;">
                        Vous avez reçu un nouveau message via le formulaire de contact de votre site,
                        le <strong><?= e($date) ?></strong>.
                    </p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;">
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;width:120px;vertical-align:top;">Nom</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;font-weight:600;"><?= e($name) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;vertical-align:top;">E-mail</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;">
                                <a href="mailto:<?= e($email) ?>" style="color:#2f6bff;text-decoration:none;font-weight:600;"><?= e($email) ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;vertical-align:top;">Sujet</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;"><?= e($subject) ?></td>
                        </tr>
                    </table>

                    <div style="margin-top:18px;color:#8a93a3;font-size:13px;text-transform:uppercase;letter-spacing:.05em;">Message</div>
                    <div style="margin-top:8px;padding:16px 18px;background:#f7f8fa;border:1px solid #eef0f3;border-radius:10px;font-size:15px;line-height:1.6;white-space:pre-wrap;color:#2b313c;"><?= nl2br(e($message)) ?></div>

                    <div style="margin-top:26px;">
                        <a href="mailto:<?= e($email) ?>?subject=<?= rawurlencode('Re: ' . $subject) ?>"
                           style="display:inline-block;background:#2f6bff;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:10px;">
                            Répondre à <?= e($name) ?>
                        </a>
                    </div>
                </td></tr>

                <!-- Pied -->
                <tr><td style="padding:18px 32px;background:#f7f8fa;border-top:1px solid #eef0f3;">
                    <p style="margin:0;font-size:12px;line-height:1.5;color:#9aa3b2;">
                        Message également consigné dans l’administration de votre site<?php if ($adminUrl !== ''): ?>
                        (<a href="<?= e($adminUrl) ?>/flow-cms/admin/contact" style="color:#9aa3b2;">Demandes de contact</a>)<?php endif; ?>.
                        Vous pouvez répondre directement à cet e-mail : votre réponse sera adressée au visiteur.
                    </p>
                </td></tr>

            </table>
        </td></tr>
    </table>
</body>
</html>
