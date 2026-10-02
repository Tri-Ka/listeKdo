<?php
/*
 * Crée la liste d'un enfant, gérée par l'utilisateur connecté.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

if (!children_enabled()) {
    fail("Les listes secondaires ne sont pas encore activées.", list_url($me['code']), 500);
}

$name = input('nom');
$theme = valid_theme(input('theme'), 'birthday');

if ('' === $name || strlen($name) > 100) {
    fail('Indiquez le nom de la liste.', list_url($me['code']));
}

$childId = child_create($me, $name, $theme);
if (!$childId) {
    fail("La liste n'a pas pu être créée.", list_url($me['code']));
}

if (event_dates_enabled()) {
    db_update('liste_user', array('event_date' => valid_date(input('event_date'))), array('id' => (int) $childId));
}

if (private_enabled() && '1' === input('is_private')) {
    db_update('liste_user', array('is_private' => 1), array('id' => (int) $childId));
}

if (upload_present('pictureFile')) {
    $upload = upload_image('pictureFile', 'uploads/' . (int) $childId, 600);
    if (isset($upload['file'])) {
        db_update('liste_user', array('pictureFile' => $upload['file']), array('id' => (int) $childId));
    }
}

$child = user_find($childId);

// Les amis du parent voient la liste de l'enfant dans leurs amis.
foreach (user_friends($me['id']) as $friend) {
    friend_add($child, $friend);
}
friend_add($child, $me);

succeed(array(), list_url($child['code']), '« ' . $child['nom'] . ' » est créée : ajoutez ses premières idées !');
