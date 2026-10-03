<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

$name = input('nom');
$password = isset($_POST['password']) ? $_POST['password'] : '';
$repeat = isset($_POST['re-password']) ? $_POST['re-password'] : '';

if ('' === $name || '' === trim($password)) {
    fail('Des champs obligatoires ne sont pas renseignés.');
}

if (strlen($name) > 100) {
    fail('Ce nom est trop long.');
}

if ($password !== $repeat) {
    fail('Les mots de passe sont différents.');
}

if (user_find_by_name($name)) {
    fail('Ce nom existe déjà.');
}

$secret = secret_enabled() ? secret_from_request('../index.php') : null;

// Parrainage : code facultatif, mais s'il est saisi il doit exister.
$sponsor = null;
if (referral_enabled() && '' !== input('referral')) {
    $sponsor = referral_find(input('referral'));
    if (!$sponsor) {
        fail("Ce code de parrainage n'existe pas. Vérifiez-le, ou laissez le champ vide.");
    }
}

$userId = user_create($name, $password, null, valid_theme(input('theme'), 'noel'));
if (!$userId) {
    fail("Le compte n'a pas pu être créé.");
}

if (upload_present('pictureFile')) {
    $upload = upload_image('pictureFile', 'uploads/' . (int) $userId, 600);

    if (isset($upload['file'])) {
        db_update('liste_user', array('pictureFile' => $upload['file']), array('id' => (int) $userId));
    } else {
        flash('Compte créé, mais la photo a été refusée : ' . $upload['error']);
    }
}

if ($secret) {
    secret_set(array('id' => $userId), $secret[0], $secret[1]);
}

if ($sponsor) {
    db_update('liste_user', array('referred_by' => (int) $sponsor['id']), array('id' => (int) $userId));
    // Ils deviennent amis, pour que le filleul voie tout de suite la liste de son parrain.
    db_insert('user_friend', array('user_id' => (int) $userId, 'friend_code' => $sponsor['code']));
}

$user = user_find($userId);
login($user);
if (secret_enabled() && !$secret) {
    $_SESSION['kdo_ask_secret'] = true;
}

succeed(array(), list_url($user['code']), empty($_SESSION['kdo_flash']) ? 'Bienvenue ! Ajoutez votre première idée.' : '');
