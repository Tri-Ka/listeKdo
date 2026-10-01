<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

list($object, $owner) = managed_object($me, input_int('object_id'));
$back = list_url($owner['code']);

$name = input('nom');
if ('' === $name) {
    fail("Donnez un nom à l'idée.", $back);
}

$isCollection = '1' === input('collection') && items_enabled();
list($itemNames, $itemIds, $itemCount) = items_from_request();
if ($isCollection && 0 === $itemCount) {
    fail('Ajoutez au moins un élément à la collection.', $back);
}

$changes = array(
    'nom' => $name,
    'description' => input('description'),
    'link' => safe_url(input('link')),
);
if (prices_enabled()) {
    $changes['price'] = parse_amount(input('price'));
}

if (upload_present('file')) {
    $upload = upload_image('file', 'uploads/img', 1600);

    if (!isset($upload['file'])) {
        fail($upload['error'], $back);
    }

    $changes['file'] = $upload['file'];
    $changes['image_url'] = '';
} else {
    // Le champ « lien vers l'image » est pré-rempli avec l'image actuelle :
    // on ne la remplace que s'il a été modifié.
    $image = safe_image_url(input('image'));

    if ($image !== object_image_url($object)) {
        $changes['file'] = null;
        $changes['image_url'] = $image;
    }
}

db_update('liste_noel', $changes, array('id' => (int) $object['id']));

// Décocher « collection » supprime les éléments.
items_save($object, $isCollection ? $itemNames : array(), $itemIds);

succeed(array(), $back . '#card-' . (int) $object['id'], 'Idée modifiée.');
