<?php
/*
 * « Reçu » : archive une idée de sa liste (ou la remet), après l'événement.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

// Une suggestion se marque « reçue » par un gestionnaire ou par son auteur (managed_object() → suggestion_editable()) :
// elle rejoint alors les « Reçus » de son destinataire.
list($object, $owner) = managed_object($me, input_int('id'));
$suggestion = is_suggestion($object);

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
    'canEdit' => can_manage($me, $owner),
    'canGift' => !$isOwner,
    'canSuggest' => can_suggest($me, $owner),
    'private' => is_private_list($owner),
    'canView' => true,
    'children' => array(),
    'ownerChildren' => array(),
);

succeed(array(
    'received' => $received,
    'card' => render('partials/card', array('object' => $object, 'ctx' => $ctx)),
    'dialog' => render('partials/object_dialog', array('object' => $object, 'ctx' => $ctx)),
), list_url($owner['code']), $received
    ? ($suggestion ? 'Suggestion rangée dans les « Reçus » de ' . $owner['nom'] . '.' : 'Idée archivée dans « Reçus ».')
    : 'Idée remise dans la liste.');
