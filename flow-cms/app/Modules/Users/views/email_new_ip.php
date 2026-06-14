<?php
/**
 * E-mail HTML envoyé au support à chaque connexion admin depuis une IP inconnue.
 *
 * Mise en forme par CSS *inline* (les clients mail ignorent les feuilles
 * externes). Tout est échappé avec e().
 *
 * @var string $siteName
 * @var string $userName
 * @var string $userEmail
 * @var string $ip
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
                <tr><td style="background:#7a3d00;padding:26px 32px;">
                    <div style="color:#f3c98b;font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;"><?= e($siteName) ?></div>
                    <div style="color:#ffffff;font-size:21px;font-weight:700;">⚠️ Connexion depuis une nouvelle adresse IP</div>
                </td></tr>

                <!-- Corps -->
                <tr><td style="padding:28px 32px;">
                    <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#444c5a;">
                        Une connexion à l’administration de <strong><?= e($siteName) ?></strong> a réussi
                        le <strong><?= e($date) ?></strong> depuis une adresse IP jamais utilisée
                        jusqu’ici par ce compte.
                    </p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;">
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;width:120px;vertical-align:top;">Compte</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;font-weight:600;"><?= e($userName) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;vertical-align:top;">E-mail</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;"><?= e($userEmail) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;color:#8a93a3;vertical-align:top;">Adresse IP</td>
                            <td style="padding:10px 0;border-top:1px solid #eef0f3;font-weight:600;font-family:Menlo,Consolas,monospace;"><?= e($ip) ?></td>
                        </tr>
                    </table>

                    <p style="margin:22px 0 0;padding:14px 16px;background:#fff4e5;border:1px solid #f3c98b;border-radius:10px;font-size:14px;line-height:1.5;color:#7a3d00;">
                        S’il s’agit d’une connexion légitime, aucune action n’est nécessaire : cette
                        adresse est désormais considérée comme connue. Sinon, changez immédiatement le
                        mot de passe du compte concerné.
                    </p>
                </td></tr>

                <!-- Pied -->
                <tr><td style="padding:18px 32px;background:#f7f8fa;border-top:1px solid #eef0f3;">
                    <p style="margin:0;font-size:12px;line-height:1.5;color:#9aa3b2;">
                        Alerte de sécurité automatique<?php if ($adminUrl !== ''): ?> — site
                        <a href="<?= e($adminUrl) ?>" style="color:#9aa3b2;"><?= e($adminUrl) ?></a><?php endif; ?>.
                    </p>
                </td></tr>

            </table>
        </td></tr>
    </table>
</body>
</html>
