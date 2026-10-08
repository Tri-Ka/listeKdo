<?php
/* Ajoute un produit à une collection existante, notamment depuis l'extension. */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

list($object, $owner) = managed_object($me, input_int('object_id'));
$back = list_url($owner['code']);
if (is_suggestion($object) || !can_manage($me, $owner) || !items_enabled()) {
    fail('Cette collection n\'existe pas.', $back, 404);
}
$object = object_full($object);
if (!$object['is_collection'] || $object['received']) {
    fail('Choisissez une collection encore dans la liste.', $back, 409);
}
$name = substr(input('nom'), 0, 255);
if ('' === $name) {
    fail('Donnez un nom à l\'élément.', $back);
}
if ('' !== input('link') && !items_links_enabled()) {
    fail('Les liens des éléments ne sont pas encore activés.', $back, 409);
}

$position = db_one('SELECT MAX(position) AS last_position FROM liste_item WHERE product_id = ?', array((int) $object['id']));
$values = array('product_id' => (int) $object['id'], 'nom' => $name, 'position' => (int) $position['last_position'] + 1);
if (items_links_enabled()) {
    $values['link'] = safe_url(input('link'));
}
$id = db_insert('liste_item', $values);
if (!$id) {
    fail('L\'élément n\'a pas pu être enregistré.', $back);
}

succeed(array('id' => (int) $object['id'], 'item_id' => (int) $id), $back . '#card-' . (int) $object['id'], 'Élément ajouté à la collection !');
