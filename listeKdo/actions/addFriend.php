<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$friend = user_find_by_code(input('friendCode'));

if (!$friend || (int) $friend['id'] === (int) $me['id']) {
    fail("Cette personne n'existe pas.");
}

friend_add($me, $friend);

succeed(array(), list_url($friend['code']), $friend['nom'] . ' fait maintenant partie de vos amis.');
