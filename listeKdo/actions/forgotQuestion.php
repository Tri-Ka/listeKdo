<?php
/*
 * Mot de passe oublié, étape 1 : la question secrète d'un compte, à partir du nom.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

$user = secret_enabled() ? user_find_by_name(input('nom')) : null;

if (!$user || '' === (string) $user['secret_question'] || '' === (string) $user['password']) {
    fail("Pas de question secrète pour ce nom. Demandez à l'administrateur du site de réinitialiser votre mot de passe.", '../index.php', 404);
}

if ($user['secret_locked_until'] && strtotime($user['secret_locked_until']) > time()) {
    fail('Trop de mauvaises réponses : réessayez dans ' . SECRET_LOCK_MINUTES . ' minutes.', '../index.php', 429);
}

succeed(array('question' => $user['secret_question']));
