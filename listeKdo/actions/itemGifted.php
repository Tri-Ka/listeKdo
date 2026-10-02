<?php
/*
 * Réserve (gift=1) ou libère (gift=0) un élément d'une collection.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$item = items_enabled() ? db_one('SELECT * FROM liste_item WHERE id = ?', array(input_int('id'))) : null;
$object = $item ? visible_object($me, $item['product_id']) : null;

if (!$object || (int) $object['user_id'] === (int) $me['id']) {
    fail("Cet élément n'existe pas.", '../index.php', 404);
}

$owner = user_find($object['user_id']);
$back = list_url($owner['code'], '#idea-' . (int) $object['id']);

if ('1' === input('gift')) {
    if (null !== $item['gifted_by'] && (int) $item['gifted_by'] !== (int) $me['id']) {
        fail("Quelqu'un a déjà prévu d'offrir cet élément.", $back, 409);
    }
    db_update('liste_item', array('gifted_by' => (int) $me['id']), array('id' => (int) $item['id']));
} else {
    if ((int) $item['gifted_by'] !== (int) $me['id']) {
        fail("Cet élément n'est pas offert par vous.", $back, 409);
    }
    db_update('liste_item', array('gifted_by' => null), array('id' => (int) $item['id']));
}

// Une notification par collection : elle reste tant que l'ami y réserve au moins un élément.
$stillGifting = db_one('SELECT id FROM liste_item WHERE product_id = ? AND gifted_by = ?', array((int) $object['id'], (int) $me['id']));
notify_gift($me['id'], $object['id'], NOTIF_GIFT, (bool) $stillGifting);

$object = object_full($object);
$ctx = array('me' => $me, 'owner' => $owner, 'isOwner' => false, 'canGift' => true);

succeed(array(
    'id' => (int) $object['id'],
    'complete' => $object['complete'],
    'card' => render('partials/items', array('object' => $object, 'ctx' => $ctx, 'limit' => 4)),
    'detail' => render('partials/items', array('object' => $object, 'ctx' => $ctx, 'limit' => 0)),
    'gift' => render('partials/gift', array('object' => $object, 'ctx' => $ctx)),
), $back);
