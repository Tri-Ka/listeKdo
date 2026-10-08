<?php
/* Archive ou remet un élément, uniquement pour les gestionnaires de sa liste. */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$item = items_received_enabled() ? db_one('SELECT * FROM liste_item WHERE id = ?', array(input_int('id'))) : null;
if (!$item) {
    fail('Cet élément n\'existe pas.', list_url($me['code']), 404);
}
list($object, $owner) = managed_object($me, $item['product_id']);
if (!can_manage($me, $owner)) {
    fail('Cet élément n\'existe pas.', list_url($me['code']), 404);
}

$received = empty($item['received_at']);
if (!db_update('liste_item', array('received_at' => $received ? db_now() : null), array('id' => (int) $item['id']))) {
    fail('L\'élément n\'a pas pu être modifié.', list_url($owner['code']));
}
if (!$received && received_enabled() && !empty($object['received_at'])) {
    db_update('liste_noel', array('received_at' => null), array('id' => (int) $object['id']));
}
$objects = objects_for_user($owner['id']);
$object = $objects[$object['id']];
$isOwner = (int) $me['id'] === (int) $owner['id'];
$ctx = array(
    'me' => $me, 'owner' => $owner, 'isOwner' => $isOwner,
    'isFriend' => false, 'canEdit' => true, 'canGift' => !$isOwner,
    'private' => is_private_list($owner), 'canView' => true,
    'children' => array(), 'ownerChildren' => array(),
);

succeed(array(
    'id' => (int) $object['id'],
    'received' => $object['received'],
    'card' => render('partials/card', array('object' => $object, 'ctx' => $ctx)),
    'dialog' => render('partials/object_dialog', array('object' => $object, 'ctx' => $ctx)),
), list_url($owner['code']), $received ? 'Élément marqué comme reçu.' : 'Élément remis dans la liste.');
