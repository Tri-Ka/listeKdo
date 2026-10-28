<?php
/*
 * Mot de passe oublié par e-mail : lien de nouveau mot de passe envoyé à l'adresse du compte,
 * retrouvé par son nom ou son adresse. La réponse est la même dans tous les cas, pour ne pas
 * révéler quels comptes existent ni leur adresse.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

if (!mail_enabled()) {
    fail("L'envoi d'e-mails n'est pas disponible sur ce site.", '../index.php', 404);
}

$user = user_find_by_login(input('nom'));

if ($user && '' !== (string) $user['password'] && '' !== (string) $user['email']) {
    mail_reset_link($user);
}

succeed(array(), '../index.php', "Si une adresse e-mail est enregistrée pour ce compte, un lien vient d'y être envoyé. Pensez à regarder dans les indésirables.");
