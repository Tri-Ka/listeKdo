<?php
/*
 * Page « ListeKdo a déménagé », à envoyer À LA PLACE de listeKdo/index.php sur le FTP de Free
 * (http://datcharrye.free.fr/listeKdo/). Ne jamais la copier dans listeKdo/ : ce dossier sert aussi à la Freebox.
 * Garde les paramètres des liens déjà partagés (?user=…, ?parrain=…, ?invite=…) dans le lien, sans redirection automatique.
 * PHP 4 (Free) : pas de syntaxe PHP 5.
 */
$target = 'https://liste-kdo.fr/';
$query = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
// Seuls les caractères d'une adresse : le paramètre ne peut pas sortir de l'URL.
if ('' !== $query && preg_match('/^[A-Za-z0-9_.~=&%+\-]*$/', $query)) {
    $target .= '?' . $query;
}
$url = htmlspecialchars($target);
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <link rel="canonical" href="<?php echo $url; ?>">
    <title>ListeKdo a déménagé</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #fff8ec;
            color: #1d2440;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(29, 36, 64, .12);
            text-align: center;
        }
        .card__head {
            padding: 34px 24px 28px;
            background: #ff5a49;
            background-image: linear-gradient(135deg, #ff5a49 0%, #ff7a59 55%, #ffb36b 100%);
            color: #fff;
        }
        .card__icon { font-size: 52px; line-height: 1; }
        .card__head h1 { margin: 14px 0 0; font-size: 26px; font-weight: 800; letter-spacing: -.3px; }
        .card__body { padding: 28px 30px 32px; }
        .card__body p { margin: 0 0 16px; font-size: 16px; line-height: 1.6; }
        .url {
            display: inline-block;
            margin: 4px 0 22px;
            padding: 10px 16px;
            border: 2px dashed #ffb2a8;
            border-radius: 14px;
            background: #fff1ee;
            color: #ff5a49;
            font-weight: 700;
            word-break: break-all;
        }
        .btn {
            display: inline-block;
            padding: 15px 32px;
            border-radius: 999px;
            background: #ff5a49;
            background-image: linear-gradient(135deg, #ff5a49, #ff7a59);
            box-shadow: 0 8px 20px rgba(255, 90, 73, .35);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
        }
        .note { margin-top: 22px !important; color: #626a85; font-size: 14px !important; }
    </style>
</head>
<body>
    <main class="card">
        <div class="card__head">
            <div class="card__icon" aria-hidden="true">🎁</div>
            <h1>ListeKdo a déménagé !</h1>
        </div>
        <div class="card__body">
            <p>Le site a une nouvelle adresse, plus rapide et sécurisée (HTTPS). Vos listes, vos idées et votre compte vous y attendent.</p>
            <a class="url" href="<?php echo $url; ?>">liste-kdo.fr</a>
            <p><a class="btn" href="<?php echo $url; ?>">Aller sur le nouveau site</a></p>
            <p class="note">Pensez à mettre à jour vos favoris.</p>
        </div>
    </main>
</body>
</html>
