<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$name = input('nom');
$password = isset($_POST['password']) ? $_POST['password'] : '';
$repeat = isset($_POST['re-password']) ? $_POST['re-password'] : '';
$back = list_url($me['code']);

if ('' === $name) {
    fail('Le nom ne peut pas être vide.', $back);
}

$existing = user_find_by_name($name);
if ($existing && (int) $existing['id'] !== (int) $me['id']) {
    fail('Ce nom est déjà pris.', $back);
}

if ('' !== $password && $password !== $repeat) {
    fail('Les mots de passe sont différents.', $back);
}

// Question secrète : modifiée seulement si une réponse est saisie (sinon on garde l'actuelle).
// Vérifiée avant tout enregistrement, pour ne pas sauver le reste du profil en affichant une erreur.
$secret = null;
if (secret_enabled()) {
    $question = input('secret_question');
    if ('__custom' === $question) {
        $question = input('secret_question_custom');
    }
    if ('' !== input('secret_answer')) {
        $secret = secret_from_request($back);
    } elseif ('' !== (string) $me['secret_question'] && '' !== $question && $question !== $me['secret_question']) {
        fail('Indiquez la réponse de votre nouvelle question secrète.', $back);
    }
}

$changes = array('nom' => $name, 'theme' => valid_theme(input('theme'), $me['theme']));

if (private_enabled() && isset($_POST['is_private'])) {
    $changes['is_private'] = '1' === input('is_private') ? 1 : 0;
}

if (event_dates_enabled() && isset($_POST['event_date'])) {
    $changes['event_date'] = valid_date(input('event_date'));
}

// Petit mot du propriétaire (colonne ajoutée par sql/2026-10-01-mot-du-proprietaire.sql).
if (array_key_exists('message', $me) && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (strlen($message) > 500) {
        fail('Le petit mot est trop long (500 caractères maximum).', $back);
    }
    $changes['message'] = '' !== $message ? $message : null;
}

// Cadre de la photo (boutique) : un de ceux achetés, ou aucun.
if (accessories_enabled() && isset($_POST['frame'])) {
    $key = accessory_choice($me, 'frame', input('frame'), $back);
    $changes['frame'] = '' !== $key ? $key : null;
}

if ('' !== $password) {
    $changes['password'] = password_make($password);
}

if (upload_present('pictureFile')) {
    $upload = upload_image('pictureFile', 'uploads/' . (int) $me['id'], 600);

    if (!isset($upload['file'])) {
        fail($upload['error'], $back);
    }

    $changes['pictureFile'] = $upload['file'];
}

db_update('liste_user', $changes, array('id' => (int) $me['id']));

if ($secret) {
    secret_set($me, $secret[0], $secret[1]);
}

// Le mot de passe fait partie de la signature du cookie : on le renouvelle.
if (isset($changes['password'])) {
    login(user_find($me['id']));
}

succeed(array(), $back, 'Profil mis à jour.');
