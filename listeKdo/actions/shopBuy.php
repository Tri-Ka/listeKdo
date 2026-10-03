<?php
/*
 * Boutique : acheter un article (habillage pour un type de liste) avec ses gemmes.
 * Il est aussitôt posé sur sa propre liste si elle est de ce type.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();
$back = list_url($me['code']);

if (!skins_enabled()) {
    fail("La boutique n'est pas encore ouverte.", $back);
}
$items = skin_items();
$key = input('skin');
if (!isset($items[$key])) {
    fail('Habillage inconnu.', $back, 404);
}
$owned = skins_owned($me['id']);
if (isset($owned[$key])) {
    fail('Vous avez déjà cet habillage.', $back, 409);
}

// Les badges du jour comptent avant l'achat.
unset($_SESSION['kdo_badges_at']);
badges_refresh($me);

$price = (int) $items[$key]['price'];
$gems = gems_of($me['id']);
if ($gems['balance'] < $price) {
    fail('Il vous manque ' . ($price - $gems['balance']) . ' gemmes pour cet habillage.', $back, 409);
}

db_insert('user_skin', array('user_id' => (int) $me['id'], 'skin' => $key, 'price' => $price, 'bought_at' => db_now()));
// Posé tout de suite sur sa liste si elle est de ce type.
$themes = themes();
$label = $items[$key]['label'] . ' · ' . $themes[$items[$key]['theme']]['label'];
if ($items[$key]['theme'] === $me['theme']) {
    db_update('liste_user', array('skin' => $key), array('id' => (int) $me['id']));
    succeed(array('balance' => $gems['balance'] - $price), $back, '« ' . $label . ' » est à vous ! Il habille maintenant votre liste.');
}
succeed(array('balance' => $gems['balance'] - $price), $back, '« ' . $label . ' » est à vous ! Choisissez-le dans les paramètres d\'une liste de ce type.');
