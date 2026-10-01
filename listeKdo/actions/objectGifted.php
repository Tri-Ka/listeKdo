<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$object = object_find(input_int('id'));

if (!$object || (int) $object['user_id'] === (int) $me['id']) {
    fail("Cette idée n'existe pas.", '../index.php', 404);
}

if (0 < count(object_items($object['id']))) {
    fail('Cette idée est une collection : choisissez les éléments à offrir.', '../index.php', 409);
}

if (0 < count(object_participants($object['id']))) {
    fail("Ce cadeau est offert à plusieurs : rejoignez les participants depuis sa fiche.", '../index.php', 409);
}

$owner = user_find($object['user_id']);
$back = list_url($owner['code'], '#idea-' . (int) $object['id']);

if (null !== $object['gifted_by'] && (int) $object['gifted_by'] !== (int) $me['id']) {
    fail("Quelqu'un a déjà prévu d'offrir ce Kdo.", $back, 409);
}

db_update('liste_noel', array('gifted_by' => (int) $me['id']), array('id' => (int) $object['id']));

$object = object_full(object_find($object['id']));
$ctx = array('me' => $me, 'canGift' => true);

succeed(array(
    'gifted' => null !== $object['gifted_by'],
    'html' => render('partials/gift', array('object' => $object, 'ctx' => $ctx)),
), $back);
