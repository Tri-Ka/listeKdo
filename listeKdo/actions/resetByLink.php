<?php
/*
 * Nouveau mot de passe choisi depuis un lien de secours, puis connexion.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

$param = input('t');
$back = '../reset.php?t=' . rawurlencode($param);
$user = reset_link_user($param);
$password = isset($_POST['password']) ? $_POST['password'] : '';
$repeat = isset($_POST['re-password']) ? $_POST['re-password'] : '';

if (!$user) {
    fail("Ce lien n'est plus valable. Demandez-en un nouveau.", $back, 410);
}

if (strlen($password) < 4) {
    fail('Le nouveau mot de passe doit faire au moins 4 caractères.', $back);
}
if ($password !== $repeat) {
    fail('Les mots de passe sont différents.', $back);
}

reset_link_use($user, $password);
login(user_find($user['id']));

succeed(array(), list_url($user['code']), 'Mot de passe changé, vous êtes connecté !');
