<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

// Suggestion : idée ajoutée sur la liste d'un ami, que son propriétaire ne verra jamais.
$isSuggestion = '1' === input('suggest');
if ($isSuggestion) {
    $owner = user_find_by_code(input('owner'));
    if (!can_suggest($me, $owner)) {
        fail('Vous ne pouvez pas suggérer d\'idée sur cette liste.', list_url($me['code']), 403);
    }
} else {
    $owner = target_owner($me);
}
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
if ($isSuggestion) {
    $values['suggested_by'] = (int) $me['id'];
}

// L'idée est ajoutée à la liste de l'utilisateur connecté, ou à celle d'un enfant qu'il gère.
$id = db_insert('liste_noel', $values);

if (!$id) {
    fail("L'idée n'a pas pu être enregistrée.", $back);
}

if ($isCollection) {
    items_save(array('id' => $id), $itemNames, array());
}

if ($isSuggestion) {
    notify($me['id'], $id, NOTIF_SUGGESTION);
    succeed(array('id' => $id), $back . '#new-' . $id, 'Suggestion ajoutée ! ' . $owner['nom'] . ' ne la verra pas.');
}

notify($me['id'], $id, NOTIF_NEW_IDEA);

// Parrainage : première idée d'un filleul, son parrain en est averti (ses gemmes sont comptées par gems_of()).
if (referral_enabled() && !empty($me['referred_by'])) {
    $ideas = db_one('SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ?' . own_ideas_sql(), array((int) $me['id']));
    if ($ideas && 1 === (int) $ideas['n']) {
        db_insert('notification', array('author_id' => (int) $me['id'], 'product_id' => 0, 'type' => NOTIF_REFERRAL, 'created_at' => db_now()));
    }
}

// #new- : la page joue l'animation d'arrivée de la nouvelle idée (js/app.js : openFromHash).
succeed(array('id' => $id), $back . '#new-' . $id, 'Idée ajoutée !');
