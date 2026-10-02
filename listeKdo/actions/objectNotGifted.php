<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$object = object_find(input_int('id'));

if (!$object || (int) $object['user_id'] === (int) $me['id']) {
    fail("Cette idée n'existe pas.", '../index.php', 404);
}

$owner = user_find($object['user_id']);
$back = list_url($owner['code'], '#idea-' . (int) $object['id']);

if ((int) $object['gifted_by'] !== (int) $me['id']) {
    fail("Ce Kdo n'est pas offert par vous.", $back, 409);
}

db_update('liste_noel', array('gifted_by' => null), array('id' => (int) $object['id']));
notify_gift($me['id'], $object['id'], NOTIF_GIFT, false);

$object = object_full(object_find($object['id']));
$ctx = array('me' => $me, 'canGift' => true);

succeed(array(
    'gifted' => null !== $object['gifted_by'],
    'html' => render('partials/gift', array('object' => $object, 'ctx' => $ctx)),
), $back);
