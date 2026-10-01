<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$friend = user_find_by_code(input('friendCode'));

if (!$friend) {
    fail("Cette personne n'existe pas.");
}

friend_remove($me, $friend);

succeed(array(), list_url($friend['code']), $friend['nom'] . " ne fait plus partie de vos amis.");
