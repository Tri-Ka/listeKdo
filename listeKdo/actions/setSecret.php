<?php
/*
 * Choisir sa question secrète (fenêtre d'invitation après la connexion).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();
$back = input('back') ? list_url(input('back')) : list_url($me['code']);

if (!secret_enabled()) {
    fail("La question secrète n'est pas encore activée.", $back, 500);
}

$secret = secret_from_request($back);
if (null === $secret) {
    fail('Choisissez une question secrète et indiquez sa réponse.', $back);
}

secret_set($me, $secret[0], $secret[1]);

succeed(array(), $back, 'Question secrète enregistrée : vous pourrez récupérer votre mot de passe.');
