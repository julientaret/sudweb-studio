<?php
/**
 * E-mail HTML de confirmation envoyé au demandeur, avec le récapitulatif de sa
 * demande de devis.
 *
 * Même rendu générique que email_owner : le formulaire étant dynamique, les
 * réponses viennent de $answers (liste ordonnée [{label, value}]). Mise en forme
 * par CSS *inline* (les clients mail ignorent les feuilles externes). Tout est
 * échappé avec e().
 *
 * @var string $siteName
 * @var string $name
 * @var string $email
 * @var array<int, array{label:string, value:string}> $answers
 * @var string $date
 * @var string $siteUrl  URL absolue du site (peut être vide)
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
                    <div style="color:#ffffff;font-size:21px;font-weight:700;">✅ Votre demande de devis</div>
                </td></tr>

                <!-- Corps -->
                <tr><td style="padding:28px 32px;">
                    <p style="margin:0 0 14px;font-size:15px;line-height:1.5;color:#444c5a;">
                        Bonjour <strong><?= e($name) ?></strong>,
                    </p>
                    <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#444c5a;">
                        Nous avons bien reçu votre demande de devis le <strong><?= e($date) ?></strong>.
                        Voici le récapitulatif des informations que vous nous avez transmises.
                        Nous reviendrons vers vous dans les meilleurs délais.
                    </p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;">
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;width:170px;vertical-align:top;">Nom</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;font-weight:600;"><?= e($name) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;vertical-align:top;">E-mail</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;"><?= e($email) ?></td>
                        </tr>
                        <?php foreach ($answers as $a): ?>
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;vertical-align:top;"><?= e($a['label']) ?></td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;white-space:pre-wrap;"><?= nl2br(e($a['value'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </td></tr>

                <!-- Pied -->
                <tr><td style="padding:18px 32px;background:#f7f8fa;border-top:1px solid #eef0f3;">
                    <p style="margin:0;font-size:12px;line-height:1.5;color:#9aa3b2;">
                        Cet e-mail vous est envoyé automatiquement pour confirmer la réception de votre demande<?php if ($siteUrl !== ''): ?>
                        sur <a href="<?= e($siteUrl) ?>" style="color:#9aa3b2;"><?= e($siteName) ?></a><?php endif; ?>.
                        Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer ce message.
                    </p>
                </td></tr>

            </table>
        </td></tr>
    </table>
</body>
</html>
