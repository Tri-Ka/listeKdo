<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$object = visible_object($me, input_int('productId'));
$content = input('content');

if (!$object) {
    fail("Cette idée n'existe pas.", '../index.php', 404);
}

$owner = user_find($object['user_id']);
$back = list_url($owner['code'], '#idea-' . (int) $object['id']);

if ('' === $content) {
    fail('Le commentaire est vide.', $back);
}

$commentId = db_insert('comment', array(
    'content' => $content,
    'product_id' => (int) $object['id'],
    'user_id' => (int) $me['id'],
));

if (!$commentId) {
    fail("Le commentaire n'a pas pu être enregistré.", $back);
}

notify($me['id'], $object['id'], NOTIF_COMMENT);

$comment = array('id' => $commentId, 'user_id' => $me['id'], 'user' => $me, 'content' => $content);
$count = db_one('SELECT COUNT(*) AS total FROM comment WHERE product_id = ?', array((int) $object['id']));

succeed(array(
    'html' => render('partials/comment', array('comment' => $comment, 'ctx' => array('me' => $me))),
    'count' => (int) $count['total'],
), $back);
