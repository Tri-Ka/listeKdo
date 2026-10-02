<?php
/*
 * Cadeau à plusieurs : rejoindre (action=join, montant facultatif) ou quitter (action=leave).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$object = visible_object($me, input_int('id'));
if (!$object || !participations_enabled() || (int) $object['user_id'] === (int) $me['id']) {
    fail("Cette idée n'existe pas.", '../index.php', 404);
}

$owner = user_find($object['user_id']);
$back = list_url($owner['code'], '#idea-' . (int) $object['id']);

if (null !== $object['gifted_by']) {
    fail("Quelqu'un a déjà prévu d'offrir ce cadeau seul.", $back, 409);
}
if (0 < count(object_items($object['id']))) {
    fail('Cette idée est une collection : choisissez les éléments à offrir.', $back, 409);
}

if ('leave' === input('do')) {
    participation_remove($object['id'], $me['id']);
    notify_gift($me['id'], $object['id'], NOTIF_PARTICIPATION, false);
    $message = 'Vous ne participez plus à ce cadeau.';
} else {
    $amountText = input('amount');
    $amount = parse_amount($amountText);
    if ('' !== $amountText && null === $amount) {
        fail('Montant invalide (ex. 30 ou 29,90).', $back);
    }
    participation_set($object['id'], $me['id'], $amount);
    notify_gift($me['id'], $object['id'], NOTIF_PARTICIPATION, true);
    $message = 'Vous participez à ce cadeau !';
}

$object = object_full(object_find($object['id']));
$ctx = array('me' => $me, 'owner' => $owner, 'isOwner' => false, 'canEdit' => can_manage($me, $owner), 'canGift' => true);

succeed(array(
    'id' => (int) $object['id'],
    'complete' => $object['complete'],
    'gift' => render('partials/gift', array('object' => $object, 'ctx' => $ctx)),
    'group' => render('partials/group', array('object' => $object, 'ctx' => $ctx)),
), $back, $message);
