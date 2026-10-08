<?php
/*
 * « Reçu » : archive une idée de sa liste (ou la remet), après l'événement.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

list($object, $owner) = managed_object($me, input_int('id'));
// Une suggestion se marque « reçue » seulement par un gestionnaire, pas par son auteur.
if (is_suggestion($object) && !can_manage($me, $owner)) {
    fail("Cette idée n'existe pas.", list_url($owner['code']), 404);
}

if (!received_enabled()) {
    fail("L'archivage n'est pas encore activé (voir sql/2026-10-01-prix-participations-enfants.sql).", list_url($owner['code']), 500);
}

$full = object_full($object);
$received = !$full['received'];
object_set_received($object, $received);

// La vignette et sa fiche sont renvoyées entières : « Je l'offre », étiquettes et menu changent avec l'état.
$objects = objects_for_user($owner['id']);
$object = $objects[$object['id']];
$isOwner = (int) $me['id'] === (int) $owner['id'];
$ctx = array(
    'me' => $me,
    'owner' => $owner,
    'isOwner' => $isOwner,
    'isFriend' => false,
    'canEdit' => true,
    'canGift' => !$isOwner,
    'private' => is_private_list($owner),
    'canView' => true,
    'children' => array(),
    'ownerChildren' => array(),
);

succeed(array(
    'received' => $received,
    'card' => render('partials/card', array('object' => $object, 'ctx' => $ctx)),
    'dialog' => render('partials/object_dialog', array('object' => $object, 'ctx' => $ctx)),
), list_url($owner['code']), $received ? 'Idée archivée dans « Reçus ».' : 'Idée remise dans la liste.');
