<?php
/*
 * Confirmer son adresse e-mail avec le code à 6 chiffres reçu (op=verify), ou en recevoir un nouveau (op=resend).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();
$back = input('back') ? list_url(input('back')) : list_url($me['code']);

if (!email_unverified($me)) {
    succeed(array(), $back, '');
}

if ('resend' === input('op')) {
    $sent = email_code_send($me, (string) $me['email']);
    if ('wait' === $sent) {
        fail('Un code vient déjà de partir : attendez une minute avant d\'en demander un autre.', $back, 429);
    }
    if ('error' === $sent) {
        fail("Le code n'a pas pu être envoyé. Vérifiez l'adresse, ou réessayez dans un instant.", $back, 500);
    }
    succeed(array(), $back, 'Nouveau code envoyé à ' . $me['email'] . '.');
}

$result = email_code_check($me, input('code'));

if ('wrong' === $result) {
    fail('Ce code ne correspond pas. Vérifiez le dernier e-mail reçu.', $back, 422);
}
if ('locked' === $result) {
    fail('Trop d\'essais : demandez un nouveau code.', $back, 429);
}
if ('expired' === $result) {
    fail('Ce code a expiré : demandez-en un nouveau.', $back, 422);
}

succeed(array(), $back, 'Adresse e-mail confirmée, merci !');
