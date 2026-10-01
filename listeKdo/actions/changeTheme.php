<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$owner = target_owner($me);
$theme = input('theme');
$themes = themes();

if (!isset($themes[$theme])) {
    fail('Thème inconnu.', list_url($owner['code']));
}

db_update('liste_user', array('theme' => $theme), array('id' => (int) $owner['id']));

succeed(array(), list_url($owner['code']));
