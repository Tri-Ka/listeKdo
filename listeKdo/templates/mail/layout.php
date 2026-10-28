<?php
/*
 * Gabarit HTML des e-mails (lib/mail.php : mail_html()). Tableaux et styles en ligne : seule mise en page
 * fiable dans Gmail, Outlook et les applications mobiles. Couleurs du logo (favicon.svg) et du site.
 * Variables : $preheader, $title, $hello, $paragraphs (texte brut), $code (facultatif),
 * $button (array('label', 'url'), facultatif), $note (facultatif), $siteUrl.
 */
$coral = '#ff5a49';
$text = '#1d2440';
$muted = '#626a85';
$font = "'Outfit','Segoe UI',Roboto,Helvetica,Arial,sans-serif";
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title><?php echo e($title); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap');
        @media (max-width: 520px) {
            .card { border-radius: 0 !important; }
            .pad { padding-left: 24px !important; padding-right: 24px !important; }
            .code { font-size: 34px !important; letter-spacing: 8px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#fff8ec;">
    <?php // Aperçu affiché après le sujet dans la liste des e-mails, invisible dans le message. ?>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;"><?php echo e($preheader); ?>&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff8ec;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card" style="max-width:520px;background:#ffffff;border-radius:28px;overflow:hidden;box-shadow:0 18px 50px rgba(29,36,64,.12);">
                    <tr>
                        <td style="background:<?php echo $coral; ?>;background-image:linear-gradient(135deg,#ff5a49 0%,#ff7a59 55%,#ffb36b 100%);padding:28px 32px;" class="pad">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;padding-right:12px;">
                                        <img src="<?php echo e($siteUrl); ?>favicon-192.png" width="44" height="44" alt="" style="display:block;border:0;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.15);">
                                    </td>
                                    <td style="vertical-align:middle;font-family:<?php echo $font; ?>;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-.3px;">
                                        ListeKdo
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="pad" style="padding:36px 40px 8px;font-family:<?php echo $font; ?>;color:<?php echo $text; ?>;">
                            <h1 style="margin:0 0 18px;font-size:26px;line-height:1.25;font-weight:800;letter-spacing:-.4px;color:<?php echo $text; ?>;"><?php echo e($title); ?></h1>
                            <p style="margin:0 0 14px;font-size:16px;line-height:1.6;">Bonjour <strong><?php echo e($hello); ?></strong>,</p>
                            <?php foreach ($paragraphs as $paragraph) : ?>
                                <p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:<?php echo $text; ?>;"><?php echo e($paragraph); ?></p>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php if (!empty($code)) : ?>
                        <tr>
                            <td class="pad" style="padding:10px 40px 6px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="background:#fff1ee;border:2px dashed #ffb2a8;border-radius:20px;padding:22px 12px;">
                                            <div class="code" style="font-family:'SF Mono',Menlo,Consolas,monospace;font-size:42px;font-weight:700;letter-spacing:12px;color:<?php echo $coral; ?>;padding-left:12px;"><?php echo e($code); ?></div>
                                            <div style="margin-top:6px;font-family:<?php echo $font; ?>;font-size:13px;color:<?php echo $muted; ?>;">Votre code de confirmation</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($button)) : ?>
                        <tr>
                            <td align="center" class="pad" style="padding:14px 40px 10px;">
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="border-radius:999px;background:<?php echo $coral; ?>;background-image:linear-gradient(135deg,#ff5a49,#ff7a59);box-shadow:0 8px 20px rgba(255,90,73,.35);">
                                            <a href="<?php echo e($button[1]); ?>" style="display:inline-block;padding:15px 34px;font-family:<?php echo $font; ?>;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;"><?php echo e($button[0]); ?></a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:16px 0 0;font-family:<?php echo $font; ?>;font-size:12px;line-height:1.5;color:<?php echo $muted; ?>;word-break:break-all;">Le bouton ne marche pas ? Copiez ce lien :<br><a href="<?php echo e($button[1]); ?>" style="color:<?php echo $coral; ?>;"><?php echo e($button[1]); ?></a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="pad" style="padding:18px 40px 34px;font-family:<?php echo $font; ?>;">
                            <?php if (!empty($note)) : ?>
                                <p style="margin:0;padding:14px 16px;background:#f4f6fb;border-radius:14px;font-size:14px;line-height:1.55;color:<?php echo $muted; ?>;"><?php echo e($note); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
                    <tr>
                        <td align="center" style="padding:22px 24px 0;font-family:<?php echo $font; ?>;font-size:12px;line-height:1.6;color:<?php echo $muted; ?>;">
                            🎁 Vos envies, leurs belles attentions<br>
                            <a href="<?php echo e($siteUrl); ?>" style="color:<?php echo $muted; ?>;text-decoration:underline;"><?php echo e(preg_replace('#^https?://|/$#', '', $siteUrl)); ?></a>
                            · E-mail automatique, merci de ne pas y répondre.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
