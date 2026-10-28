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

$hasImage = comment_images_enabled() && upload_present('image');

if ('' === $content && !$hasImage) {
    fail('Le commentaire est vide.', $back);
}

$values = array(
    'content' => $content,
    'product_id' => (int) $object['id'],
    'user_id' => (int) $me['id'],
);

if ($hasImage) {
    // Photo ou GIF (gardé tel quel pour rester animé, voir upload_reencode()).
    $upload = upload_image('image', COMMENT_IMAGE_DIR, 1200);
    if (isset($upload['error'])) {
        fail($upload['error'], $back);
    }
    $values['image'] = $upload['file'];
}

if (comment_dates_enabled()) {
    $values['created_at'] = db_now();
}

$commentId = db_insert('comment', $values);

if (!$commentId) {
    if ($hasImage) {
        comment_images_delete(array($values));
    }
    fail("Le commentaire n'a pas pu être enregistré.", $back);
}

notify($me['id'], $object['id'], NOTIF_COMMENT);

$comment = $values;
$comment['id'] = $commentId;
$comment['user'] = $me;
$count = db_one('SELECT COUNT(*) AS total FROM comment WHERE product_id = ?', array((int) $object['id']));

succeed(array(
    'html' => render('partials/comment', array('comment' => $comment, 'ctx' => array('me' => $me))),
    'count' => (int) $count['total'],
), $back);
