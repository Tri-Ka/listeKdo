<?php
/*
 * Supprime un utilisateur (ou une liste d'enfant) avec toutes ses idées.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_admin();

$user = user_find(input_int('id'));

if (!$user) {
    fail('Utilisateur inconnu.', '../admin.php', 404);
}

if ((int) $user['id'] === (int) $me['id']) {
    fail('Vous ne pouvez pas supprimer votre propre compte.', '../admin.php');
}

if (is_admin($user)) {
    fail("Retirez d'abord le rôle administrateur de " . $user['nom'] . '.', '../admin.php');
}

if (!admin_delete_user($user)) {
    fail("L'utilisateur n'a pas pu être supprimé.", '../admin.php', 500);
}

succeed(array(), '../admin.php', $user['nom'] . ' a été supprimé.');
