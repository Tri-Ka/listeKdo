<?php
/* Test ciblé des notifications de déplacement sur la base locale ; fixtures nettoyées même en cas d'erreur. */
require_once dirname(__FILE__) . '/../../listeKdo/lib/bootstrap.php';
if ('1' !== getenv('LISTEKDO_LOCAL_TEST')) {
    echo "Ce test nécessite la base locale.\n";
    exit(1);
}
$moveUsers = array();
$moveObject = null;
$failures = 0;
register_shutdown_function('move_test_cleanup');

function move_test_cleanup()
{
    if ($GLOBALS['moveObject']) object_delete($GLOBALS['moveObject']);
    $ids = $GLOBALS['moveUsers'];
    if (count($ids)) {
        db_query('DELETE FROM user_friend WHERE user_id IN (?)', array($ids));
        db_query('DELETE FROM liste_manager WHERE child_id IN (?) OR user_id IN (?)', array($ids, $ids));
        db_query('DELETE FROM liste_viewer WHERE list_id IN (?) OR user_id IN (?)', array($ids, $ids));
        db_query('DELETE FROM liste_user WHERE id IN (?)', array($ids));
    }
}

function move_test_user($label)
{
    $id = user_create('Test1move-' . $label . '-' . uniqid(''), 'fixture', '', 'birthday');
    if (!$id) exit(1);
    $GLOBALS['moveUsers'][] = $id;
    return user_find($id);
}

function move_check($ok, $label)
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . "\n";
    if (!$ok) $GLOBALS['failures']++;
}

function move_visible($user)
{
    return notifications_for($user, user_friends($user['id']), 0, 100);
}

$actor = move_test_user('auteur');
$destination = move_test_user('destination');
$friend = move_test_user('ami');
$donor = move_test_user('donateur');
$stranger = move_test_user('visiteur');
friend_add($destination, $friend);
$objectId = db_insert('liste_noel', array('user_id' => (int) $destination['id'], 'nom' => 'Déplacement <b>test</b>', 'created_at' => db_now()));
$moveObject = db_one('SELECT * FROM liste_noel WHERE id = ?', array($objectId));
if (!$moveObject) exit(1);
notify_object_move($actor, $moveObject, $actor, $actor);
$before = db_one('SELECT COUNT(*) AS n FROM notification WHERE product_id = ? AND type = ?', array($objectId, NOTIF_MOVE));
move_check(0 === (int) $before['n'], 'même liste : aucune notification');
notify_object_move($actor, $moveObject, $actor, $destination);
$after = db_one('SELECT COUNT(*) AS n FROM notification WHERE product_id = ? AND type = ?', array($objectId, NOTIF_MOVE));
move_check(1 === (int) $after['n'], 'changement de liste : une notification créée');
$notifications = move_visible($friend);
move_check(1 === count($notifications) && NOTIF_MOVE === (int) $notifications[0]['type'], 'ami de la liste d’arrivée : déplacement visible');
move_check($notifications[0]['owner_code'] === $destination['code'], 'lien : liste actuelle de l’idée');
$html = render('partials/notification_item', array('notification' => $notifications[0]));
move_check(false !== strpos($html, 'a changé la liste') && false !== strpos($html, '&lt;b&gt;test&lt;/b&gt;'), 'message explicite et titre échappé');
move_check(0 === count(move_visible($stranger)), 'personne sans lien avec l’idée : aucune notification');
move_check(0 === count(move_visible($actor)), 'auteur : pas de notification de sa propre action');
move_check(1 === count(move_visible($destination)), 'propriétaire d’arrivée : informé du déplacement');
db_update('liste_noel', array('gifted_by' => (int) $donor['id']), array('id' => $objectId));
move_check(1 === count(move_visible($donor)), 'réservation sans amitié : donateur informé');
db_update('liste_noel', array('gifted_by' => null), array('id' => $objectId));
if (participations_enabled()) {
    db_insert('liste_participation', array('product_id' => $objectId, 'user_id' => (int) $donor['id'], 'amount' => 5, 'created_at' => db_now()));
    move_check(1 === count(move_visible($donor)), 'cagnotte : participant informé');
    db_query('DELETE FROM liste_participation WHERE product_id = ?', array($objectId));
}
if (items_enabled()) {
    db_insert('liste_item', array('product_id' => $objectId, 'nom' => 'Élément', 'position' => 0, 'gifted_by' => (int) $donor['id']));
    move_check(1 === count(move_visible($donor)), 'collection : donateur d’un élément informé');
}
db_update('liste_user', array('is_private' => 1), array('id' => (int) $destination['id']));
move_check(0 === count(move_visible($friend)) && 0 === count(move_visible($donor)), 'destination privée : notification masquée aux non-invités');
db_insert('liste_viewer', array('list_id' => (int) $destination['id'], 'user_id' => (int) $friend['id']));
move_check(1 === count(move_visible($friend)) && 1 === notifications_new_count($friend, user_friends($friend['id'])), 'ami invité : notification visible et comptée comme non lue');
if (received_enabled()) {
    db_update('liste_noel', array('received_at' => db_now()), array('id' => $objectId));
    move_check(0 === count(move_visible($friend)) && 1 === count(move_visible($destination)), 'idée archivée : visible seulement à sa gestion');
}
exit($failures ? 1 : 0);
