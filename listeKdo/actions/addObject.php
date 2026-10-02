<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$owner = target_owner($me);
$back = list_url($owner['code']);
$name = input('nom');

if ('' === $name) {
    fail("Donnez un nom à l'idée.", $back);
}

// Collection : plusieurs éléments à offrir séparément (ex. les tomes d'une BD).
$isCollection = '1' === input('collection') && items_enabled();
list($itemNames, $itemIds, $itemCount) = items_from_request();
if ($isCollection && 0 === $itemCount) {
    fail('Ajoutez au moins un élément à la collection.', $back);
}

$file = null;
if (upload_present('file')) {
    $upload = upload_image('file', 'uploads/img', 1600);

    if (!isset($upload['file'])) {
        fail($upload['error'], $back);
    }

    $file = $upload['file'];
}

$values = array(
    'nom' => $name,
    'description' => input('description'),
    'image_url' => null === $file ? safe_image_url(input('image')) : '',
    'link' => safe_url(input('link')),
    'user_id' => (int) $owner['id'],
    'file' => $file,
    'created_at' => db_now(),
);
if (prices_enabled()) {
    $values['price'] = parse_amount(input('price'));
}

// L'idée est ajoutée à la liste de l'utilisateur connecté, ou à celle d'un enfant qu'il gère.
$id = db_insert('liste_noel', $values);

if (!$id) {
    fail("L'idée n'a pas pu être enregistrée.", $back);
}

if ($isCollection) {
    items_save(array('id' => $id), $itemNames, array());
}

notify($me['id'], $id, NOTIF_NEW_IDEA);

// #new- : la page joue l'animation d'arrivée de la nouvelle idée (js/app.js : openFromHash).
succeed(array('id' => $id), $back . '#new-' . $id, 'Idée ajoutée !');
