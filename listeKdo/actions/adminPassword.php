<?php
/*
 * Mot de passe provisoire pour un utilisateur qui l'a oublié (affiché une seule fois à l'admin).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_admin();

$user = user_find(input_int('id'));

if (!$user) {
    fail('Utilisateur inconnu.', '../admin.php', 404);
}

if (admin_is_child_account($user)) {
    fail("Cette liste secondaire n'a pas de mot de passe : elle est gérée par ses gestionnaires.", '../admin.php');
}

$password = admin_reset_password($user);

if (false === $password) {
    fail("Le mot de passe n'a pas pu être changé.", '../admin.php', 500);
}

succeed(array('password' => $password, 'name' => $user['nom']), '../admin.php', 'Nouveau mot de passe de ' . $user['nom'] . ' : ' . $password);
