<?php
/*
 * Poser un habillage acheté (ou revenir au classique, skin vide) sur une liste qu'on gère :
 * la sienne par défaut, ou une liste secondaire (champ owner).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$owner = target_owner($me);
$back = list_url($owner['code']);
if (!skins_enabled()) {
    fail("La boutique n'est pas encore ouverte.", $back);
}

$key = skin_choice($me, input('skin'), $owner['theme'], $back);
db_update('liste_user', array('skin' => '' !== $key ? $key : null), array('id' => (int) $owner['id']));

$items = skin_items();
succeed(array(), $back, '' !== $key ? '« ' . $items[$key]['label'] . ' » habille maintenant la liste.' : 'La liste a retrouvé son habillage classique.');
