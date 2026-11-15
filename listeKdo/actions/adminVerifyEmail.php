<?php
/*
 * Administration : confirme l'adresse e-mail d'un utilisateur à sa place (code jamais reçu, adresse vérifiée autrement).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_admin();

if (!db_has_column('liste_user', 'email_verified_at')) {
    fail("La confirmation des adresses n'est pas activée (voir sql/2026-10-28-email.sql).", '../admin.php', 500);
}

$user = user_find(input_int('id'));
if (!$user) {
    fail('Utilisateur inconnu.', '../admin.php', 404);
}
if ('' === (string) $user['email']) {
    fail("Cet utilisateur n'a pas d'adresse e-mail.", '../admin.php');
}

if ('' === (string) $user['email_verified_at'] && !db_update('liste_user', array(
    'email_verified_at' => db_now(),
    'email_code' => null,
    'email_code_expires' => null,
    'email_code_tries' => 0,
), array('id' => (int) $user['id']))) {
    fail("L'adresse n'a pas pu être confirmée.", '../admin.php', 500);
}

succeed(array('id' => (int) $user['id']), '../admin.php', 'Adresse de ' . $user['nom'] . ' confirmée.');
