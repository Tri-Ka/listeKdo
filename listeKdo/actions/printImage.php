<?php
/*
 * Photo d'une idée (id=…) ou du propriétaire d'une liste (owner=CODE) hébergée sur un autre site, servie depuis
 * le nôtre pour que le navigateur puisse la copier dans le PDF de la lettre (lib/letter.php). Lecture seule.
 * Seulement pour une personne connectée qui voit la liste ; jamais d'adresse saisie dans la requête.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../lib/letter.php';

$me = current_user();
$url = '';

if ($me && '' !== input('id')) {
    $object = object_find((int) input('id'));
    $owner = $object ? user_find($object['user_id']) : null;
    if ($owner && can_view($me, $owner) && !is_suggestion($object)) {
        $url = '' === (string) $object['file'] ? safe_image_url($object['image_url']) : '';
    }
} elseif ($me && '' !== input('owner')) {
    $owner = user_find_by_code(input('owner'));
    if ($owner && can_view($me, $owner) && '' === (string) $owner['pictureFile']) {
        $url = safe_url($owner['pictureFileUrl']);
    }
}

$image = '' !== $url ? letter_fetch_image($url) : null;

if (!$image) {
    header('HTTP/1.1 404 Not Found');
    header('Status: 404');
    exit;
}

header('Content-Type: ' . $image['type']);
header('Content-Length: ' . strlen($image['data']));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
echo $image['data'];
