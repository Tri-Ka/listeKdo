<?php
/*
 * Mot de passe oublié, étape 2 : bonne réponse à la question secrète -> nouveau mot de passe, puis connexion.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

$user = secret_enabled() ? user_find_by_name(input('nom')) : null;
$password = isset($_POST['password']) ? $_POST['password'] : '';
$repeat = isset($_POST['re-password']) ? $_POST['re-password'] : '';

if (!$user || '' === (string) $user['secret_question']) {
    fail('Pas de question secrète pour ce nom.', '../index.php', 404);
}

$result = secret_check($user, input('secret_answer'));
if ('locked' === $result) {
    fail('Trop de mauvaises réponses : réessayez dans ' . SECRET_LOCK_MINUTES . ' minutes.', '../index.php', 429);
}
if ('wrong' === $result) {
    fail('Ce n\'est pas la bonne réponse.', '../index.php', 403);
}

if (strlen($password) < 4) {
    fail('Le nouveau mot de passe doit faire au moins 4 caractères.', '../index.php');
}
if ($password !== $repeat) {
    fail('Les mots de passe sont différents.', '../index.php');
}

db_update('liste_user', array('password' => password_make($password)), array('id' => (int) $user['id']));
login(user_find($user['id']));

succeed(array('redirect' => 'index.php?user=' . rawurlencode($user['code'])), list_url($user['code']), 'Mot de passe changé, vous êtes connecté !');
