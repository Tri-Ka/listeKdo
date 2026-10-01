<?php
/*
 * « Reçu » : archive une idée de sa liste (ou la remet), après l'événement.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

list($object, $owner) = managed_object($me, input_int('id'));

if (!received_enabled()) {
    fail("L'archivage n'est pas encore activé (voir sql/2026-10-01-prix-participations-enfants.sql).", list_url($owner['code']), 500);
}

$received = empty($object['received_at']);
object_set_received($object, $received);

succeed(array('received' => $received), list_url($owner['code']), $received ? 'Idée archivée dans « Reçus ».' : 'Idée remise dans la liste.');
