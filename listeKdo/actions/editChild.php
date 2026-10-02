<?php
/*
 * Modifie la liste d'un enfant géré : prénom, photo, type de liste, date, petit mot.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$child = target_owner($me);
$back = list_url($child['code']);

if ((int) $child['id'] === (int) $me['id']) {
    fail('Utilisez « Mon profil » pour votre propre liste.', $back);
}

$name = input('nom');
if ('' === $name) {
    fail('Le prénom ne peut pas être vide.', $back);
}

$changes = array('nom' => $name, 'theme' => valid_theme(input('theme'), $child['theme']));

if (event_dates_enabled() && isset($_POST['event_date'])) {
    $changes['event_date'] = valid_date(input('event_date'));
}

if (array_key_exists('message', $child) && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $changes['message'] = '' !== $message ? substr($message, 0, 500) : null;
}

if (upload_present('pictureFile')) {
    $upload = upload_image('pictureFile', 'uploads/' . (int) $child['id'], 600);
    if (!isset($upload['file'])) {
        fail($upload['error'], $back);
    }
    $changes['pictureFile'] = $upload['file'];
}

db_update('liste_user', $changes, array('id' => (int) $child['id']));

succeed(array(), $back, 'Liste mise à jour.');
