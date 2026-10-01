<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$comment = db_one('SELECT * FROM comment WHERE id = ? AND user_id = ?', array(input_int('id'), (int) $me['id']));

if (!$comment) {
    fail("Ce commentaire n'existe pas.", '../index.php', 404);
}

db_query('DELETE FROM comment WHERE id = ?', array((int) $comment['id']));

$object = object_find($comment['product_id']);
$owner = $object ? user_find($object['user_id']) : null;
$count = db_one('SELECT COUNT(*) AS total FROM comment WHERE product_id = ?', array((int) $comment['product_id']));

succeed(array('count' => (int) $count['total']), $owner ? list_url($owner['code'], '#idea-' . (int) $object['id']) : '../index.php');
