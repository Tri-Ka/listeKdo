<?php
/*
 * Marque une notification comme lue (read=1) ou non lue (read=0).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

if (!notification_states_enabled()) {
    fail("Le suivi lu / non lu n'est pas encore activé (voir sql/2026-10-01-notifications-lues.sql).", '../index.php', 500);
}

$notification = db_one('SELECT id FROM notification WHERE id = ?', array(input_int('id')));
if (!$notification) {
    fail("Cette notification n'existe pas.", '../index.php', 404);
}

notification_set_read($me, $notification['id'], '0' !== input('read'));

succeed(array('unread' => notifications_new_count($me, user_friends($me['id']))));
