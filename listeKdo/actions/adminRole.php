<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_admin();

$user = user_find(input_int('id'));
$role = input('role');
$roles = roles();

if (!$user || !isset($roles[$role])) {
    fail('Utilisateur ou rôle inconnu.', '../admin.php', 404);
}

if ((int) $user['id'] === (int) $me['id']) {
    fail('Vous ne pouvez pas changer votre propre rôle.', '../admin.php');
}

if ('admin' === $role && admin_is_child_account($user)) {
    fail("Une liste d'enfant ne peut pas être administrateur : elle n'a pas de mot de passe.", '../admin.php');
}

admin_set_role($user, $role);

succeed(array('role' => $role), '../admin.php', $user['nom'] . ' est maintenant ' . strtolower($roles[$role]) . '.');
