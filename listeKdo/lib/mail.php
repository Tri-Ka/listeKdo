<?php
/*
 * Adresse e-mail des comptes et envoi d'e-mails en texte brut avec mail()
 * (msmtp vers Gmail sur la Freebox, MailDev en local : docker/php8/start.sh).
 * - email_enabled() : colonne liste_user.email présente (sql/2026-10-28-email.sql). L'adresse est alors
 *   obligatoire (inscription, profil, fenêtre bloquante pour les anciens comptes) et sert à se connecter.
 * - mail_enabled() : en plus, un expéditeur dans config.php ($_mail_from) : lien de mot de passe par e-mail.
 * Sans la colonne (chez Free), rien ne change sur le site.
 */

define('MAIL_RESEND_MINUTES', 5);

function email_enabled()
{
    return db_has_column('liste_user', 'email');
}

function mail_enabled()
{
    return '' !== KDO_MAIL_FROM && email_enabled() && reset_links_enabled();
}

/**
 * Compte à partir de ce qu'on tape pour se connecter : son nom, ou son adresse e-mail.
 */
function user_find_by_login($login)
{
    $email = email_enabled() ? valid_email($login) : false;

    if ($email) {
        $user = db_one('SELECT * FROM liste_user WHERE email = ? LIMIT 1', array($email));
        if ($user) {
            return $user;
        }
    }

    return user_find_by_name($login);
}

/**
 * Adresse obligatoire saisie dans un formulaire (inscription, profil, fenêtre d'ajout) :
 * nettoyée, ou fail() si vide, invalide ou déjà prise par un autre compte.
 */
function email_from_request($back, $userId = 0)
{
    $email = valid_email(input('email'));

    if ('' === $email) {
        fail('Indiquez votre adresse e-mail.', $back);
    }
    if (false === $email) {
        fail("Cette adresse e-mail n'est pas valide.", $back);
    }
    if (db_one('SELECT id FROM liste_user WHERE email = ? AND id <> ? LIMIT 1', array($email, (int) $userId))) {
        fail('Cette adresse e-mail est déjà utilisée par un autre compte.', $back);
    }

    return $email;
}

/**
 * Compte connecté sans adresse : la fenêtre « Ajoutez votre e-mail » s'affiche sur chaque page.
 */
function email_missing($user)
{
    return $user && email_enabled() && '' === (string) $user['email'] && '' !== (string) $user['password'];
}

/* ---------- Confirmation de l'adresse par un code à 6 chiffres ---------- */

define('EMAIL_CODE_MINUTES', 30);
define('EMAIL_CODE_TRIES', 5);
define('EMAIL_CODE_RESEND_SECONDS', 60);

/**
 * La confirmation n'est demandée que si les e-mails partent vraiment (sinon personne ne recevrait le code).
 */
function email_verification_enabled()
{
    return mail_enabled() && db_has_column('liste_user', 'email_verified_at');
}

/**
 * Adresse saisie mais pas encore confirmée : la fenêtre « Saisissez le code » s'affiche sur chaque page.
 */
function email_unverified($user)
{
    return $user && email_verification_enabled() && '' !== (string) $user['email']
        && '' === (string) $user['email_verified_at'] && '' !== (string) $user['password'];
}

/**
 * Adresse confirmée (ou confirmation non demandée) : elle peut recevoir un lien de mot de passe.
 */
function email_confirmed($user)
{
    return '' !== (string) $user['email'] && (!email_verification_enabled() || '' !== (string) $user['email_verified_at']);
}

function email_code_hash($user, $code)
{
    return sha1((int) $user['id'] . ':' . $code);
}

/**
 * Enregistre une nouvelle adresse (à confirmer) et lui envoie un code. Retourne 'sent', 'wait' (code envoyé
 * il y a moins de EMAIL_CODE_RESEND_SECONDS secondes à la même adresse) ou 'error'.
 */
function email_code_send($user, $email)
{
    $sameEmail = $email === (string) $user['email'];
    if ($sameEmail && '' !== (string) $user['email_code_expires']
        && strtotime($user['email_code_expires']) - EMAIL_CODE_MINUTES * 60 > time() - EMAIL_CODE_RESEND_SECONDS) {
        return 'wait';
    }

    // Code à 6 chiffres tiré du générateur sûr de random_token() (40 caractères hexadécimaux).
    $code = sprintf('%06d', hexdec(substr(random_token(), 0, 7)) % 1000000);
    $expires = time() + EMAIL_CODE_MINUTES * 60;

    db_update('liste_user', array(
        'email' => $email,
        'email_verified_at' => null,
        'email_code' => email_code_hash($user, $code),
        'email_code_expires' => date('Y-m-d H:i:s', $expires),
        'email_code_tries' => 0,
    ), array('id' => (int) $user['id']));

    $sent = kdo_mail($email, 'Votre code : ' . $code, array(
        'preheader' => 'Votre code de confirmation : ' . $code,
        'title' => 'Confirmez votre adresse e-mail',
        'hello' => $user['nom'],
        'paragraphs' => array('Voici votre code pour confirmer votre adresse sur Liste de Kdo. Saisissez-le dans la fenêtre ouverte sur le site.'),
        'code' => $code,
        'note' => 'Il est valable ' . EMAIL_CODE_MINUTES . " minutes. Si vous n'avez rien demandé, ignorez simplement cet e-mail.",
    ));

    return $sent ? 'sent' : 'error';
}

/**
 * Vérifie le code saisi. Retourne 'ok', 'wrong', 'expired' ou 'locked' (trop d'essais : demander un nouveau code).
 */
function email_code_check($user, $code)
{
    if ('' === (string) $user['email_code'] || strtotime($user['email_code_expires']) < time()) {
        return 'expired';
    }
    if ((int) $user['email_code_tries'] >= EMAIL_CODE_TRIES) {
        return 'locked';
    }

    $code = preg_replace('/[^0-9]/', '', (string) $code);
    if (!secure_equals($user['email_code'], email_code_hash($user, $code))) {
        db_query('UPDATE liste_user SET email_code_tries = email_code_tries + 1 WHERE id = ?', array((int) $user['id']));

        return (int) $user['email_code_tries'] + 1 >= EMAIL_CODE_TRIES ? 'locked' : 'wrong';
    }

    db_update('liste_user', array(
        'email_verified_at' => db_now(),
        'email_code' => null,
        'email_code_expires' => null,
        'email_code_tries' => 0,
    ), array('id' => (int) $user['id']));

    return 'ok';
}

/**
 * Enregistre l'adresse d'un compte : avec confirmation par code si les e-mails partent, sinon telle quelle.
 * Retourne le message à afficher ('' si rien n'a changé). Même si le code n'a pas pu partir, l'adresse est
 * gardée : la fenêtre de confirmation propose « Renvoyer le code ».
 */
function email_save($user, $email)
{
    if (!email_verification_enabled()) {
        if ($email !== (string) $user['email']) {
            db_update('liste_user', array('email' => $email), array('id' => (int) $user['id']));
        }

        return '';
    }

    if ($email === (string) $user['email'] && email_confirmed($user)) {
        return '';
    }

    if ('error' === email_code_send($user, $email)) {
        return "Le code n'a pas pu être envoyé à " . $email . ' : vérifiez l\'adresse, ou cliquez sur « Renvoyer le code ».';
    }

    return 'Un code à 6 chiffres vient d\'être envoyé à ' . $email . '.';
}

/**
 * Adresse e-mail nettoyée (minuscules), '' si vide, false si invalide.
 */
function valid_email($value)
{
    $value = strtolower(trim((string) $value));

    if ('' === $value) {
        return '';
    }

    if (strlen($value) > 190 || !preg_match('/^[a-z0-9._%+\'-]+@[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/D', $value)) {
        return false;
    }

    return $value;
}

/**
 * En-tête encodé en UTF-8 (sujet, nom de l'expéditeur) : les accents passent partout.
 */
function mail_header_text($text)
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

/**
 * Envoie un e-mail en deux versions, HTML (templates/mail/layout.php) et texte brut pour les messageries
 * qui n'affichent pas le HTML. $mail : preheader, title, hello, paragraphs, et en option code, button
 * (array(libellé, url)) et note. $to doit déjà être validé par valid_email().
 */
function kdo_mail($to, $subject, $mail)
{
    if (!mail_enabled() || false === valid_email($to) || '' === $to) {
        return false;
    }

    $mail['siteUrl'] = site_base_url();
    foreach (array('code', 'button', 'note') as $key) {
        if (!isset($mail[$key])) {
            $mail[$key] = null;
        }
    }

    $boundary = 'kdo-' . substr(random_token(), 0, 24);
    $headers = 'From: ' . mail_header_text('Liste de Kdo') . ' <' . KDO_MAIL_FROM . ">\r\n"
        . "MIME-Version: 1.0\r\n"
        . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    // base64 : lignes courtes et accents intacts quel que soit le relais.
    $body = '--' . $boundary . "\n"
        . "Content-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n"
        . chunk_split(base64_encode(mail_text($mail)), 76, "\n") . "\n"
        . '--' . $boundary . "\n"
        . "Content-Type: text/html; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n"
        . chunk_split(base64_encode(render('mail/layout', $mail)), 76, "\n") . "\n"
        . '--' . $boundary . "--\n";

    return @mail($to, mail_header_text($subject), $body, $headers);
}

/**
 * Version texte brut d'un e-mail (mêmes contenus que le gabarit HTML).
 */
function mail_text($mail)
{
    $text = 'Bonjour ' . $mail['hello'] . ",\n\n" . implode("\n\n", $mail['paragraphs']) . "\n\n";
    if ($mail['code']) {
        $text .= '    ' . $mail['code'] . "\n\n";
    }
    if ($mail['button']) {
        $text .= $mail['button'][0] . " :\n" . $mail['button'][1] . "\n\n";
    }
    if ($mail['note']) {
        $text .= $mail['note'] . "\n\n";
    }

    return $text . "— Liste de Kdo\n" . $mail['siteUrl'] . "\n";
}

/**
 * Lien pour choisir un nouveau mot de passe, envoyé à l'adresse du compte.
 * Pas de nouvel envoi pendant MAIL_RESEND_MINUTES, pour ne pas inonder la boîte de quelqu'un.
 */
function mail_reset_link($user)
{
    // Jamais vers une adresse que la personne n'a pas confirmée.
    if (!mail_enabled() || !email_confirmed($user)) {
        return false;
    }

    if ('' !== (string) $user['reset_expires']
        && strtotime($user['reset_expires']) - RESET_LINK_HOURS * 3600 > time() - MAIL_RESEND_MINUTES * 60) {
        return true;
    }

    $link = reset_link_create($user);

    if (!$link) {
        return false;
    }

    return kdo_mail($user['email'], 'Votre nouveau mot de passe', array(
        'preheader' => 'Choisissez un nouveau mot de passe en un clic.',
        'title' => 'Nouveau mot de passe',
        'hello' => $user['nom'],
        'paragraphs' => array('Vous avez demandé à changer votre mot de passe sur Liste de Kdo. Cliquez sur le bouton pour en choisir un nouveau.'),
        'button' => array('Choisir mon mot de passe', $link['url']),
        'note' => 'Ce lien fonctionne une seule fois, jusqu\'au ' . date('d/m/Y à H:i', $link['expires'])
            . ". Si vous n'avez rien demandé, ignorez cet e-mail : votre mot de passe ne change pas.",
    ));
}
