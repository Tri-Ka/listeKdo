<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

// Nom ou adresse e-mail.
$user = user_find_by_login(input('nom'));
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$user || !password_matches($password, $user['password'])) {
    fail(email_enabled() ? 'Identifiant ou mot de passe invalide.' : 'Nom ou mot de passe invalide.', list_url(input('redirect')));
}

// Les anciens mots de passe (MD5 sans sel) sont convertis au nouveau format.
if (password_is_legacy($user['password'])) {
    $user['password'] = password_make($password);
    db_update('liste_user', array('password' => $user['password']), array('id' => (int) $user['id']));
}

login($user);

// Pas encore de question secrète : une fenêtre invitera à en choisir une.
if (secret_enabled() && '' === (string) $user['secret_question']) {
    $_SESSION['kdo_ask_secret'] = true;
}

$redirect = input('redirect');
succeed(array(), list_url('' !== $redirect ? $redirect : $user['code']));
