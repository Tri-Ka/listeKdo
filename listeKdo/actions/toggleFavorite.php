<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

list($object, $owner) = managed_object($me, input_int('id'));

$favorite = empty($object['favorite']);

if (!object_set_favorite($object, $favorite)) {
    fail("Les coups de cœur ne sont pas encore activés (voir sql/2026-10-01-coups-de-coeur.sql).", list_url($owner['code']), 500);
}

succeed(array('favorite' => $favorite), list_url($owner['code'], '#card-' . (int) $object['id']));
