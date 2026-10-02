<?php
/*
 * Lien de secours (créé par un admin) : choisir un nouveau mot de passe.
 */
require_once 'lib/bootstrap.php';

$param = input('t');

echo render('reset', array(
    'param' => $param,
    'user' => reset_link_user($param),
    'flash' => flash_take(),
));
