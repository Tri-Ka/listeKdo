<?php
/*
 * Page de notifications pour le panneau : « Voir plus » et onglet « Non lues » (filter=unread).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
$me = require_login();

$offset = max(0, input_int('offset'));
$unreadOnly = 'unread' === input('filter');
$friends = user_friends($me['id']);
$items = notifications_for($me, $friends, $offset, NOTIFICATIONS_PER_PAGE + 1, $unreadOnly);

$html = '';
foreach (array_slice($items, 0, NOTIFICATIONS_PER_PAGE) as $notification) {
    $html .= render('partials/notification_item', array('notification' => $notification));
}

send_json(array(
    'ok' => true,
    'html' => $html,
    'hasMore' => count($items) > NOTIFICATIONS_PER_PAGE,
    'offset' => $offset + NOTIFICATIONS_PER_PAGE,
    'unread' => notifications_new_count($me, $friends),
));
