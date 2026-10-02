<?php
/*
 * Supprime la liste d'un enfant géré, avec toutes ses idées.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$child = target_owner($me);

if ((int) $child['id'] === (int) $me['id']) {
    fail('Vous ne pouvez pas supprimer votre propre compte ici.', list_url($me['code']));
}

foreach (db_all('SELECT * FROM liste_noel WHERE user_id = ?', array((int) $child['id'])) as $object) {
    object_delete($object);
}
db_query('DELETE FROM user_friend WHERE user_id = ? OR friend_code = ?', array((int) $child['id'], $child['code']));
db_query('DELETE FROM liste_manager WHERE child_id = ?', array((int) $child['id']));
db_query('DELETE FROM liste_user WHERE id = ?', array((int) $child['id']));
upload_purge_orphan_images();

succeed(array(), list_url($me['code']), 'La liste de ' . $child['nom'] . ' a été supprimée.');
