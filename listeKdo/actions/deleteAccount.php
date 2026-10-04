<?php
/*
 * Suppression de son propre compte, depuis « Mon profil » (mot de passe demandé).
 * Supprime aussi les listes secondaires dont on est le seul gestionnaire.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$back = list_url($me['code']);
$password = isset($_POST['current_password']) ? $_POST['current_password'] : '';

if (!password_matches($password, $me['password'])) {
    fail('Mot de passe incorrect : le compte n\'a pas été supprimé.', $back);
}

if (is_admin($me)) {
    $admins = db_one("SELECT COUNT(*) AS n FROM liste_user WHERE role = 'admin'");
    if ((int) $admins['n'] <= 1) {
        fail("Vous êtes le seul administrateur : nommez-en un autre avant de supprimer votre compte.", $back);
    }
}

// Listes secondaires qui n'auraient plus personne pour les gérer.
foreach (user_children($me['id']) as $child) {
    if (1 === count(child_managers($child['id']))) {
        admin_delete_user($child);
    }
}

if (!admin_delete_user($me)) {
    fail("Le compte n'a pas pu être supprimé.", $back, 500);
}

logout();
session_start();

succeed(array(), '../index.php', 'Votre compte a été supprimé. À bientôt !');
