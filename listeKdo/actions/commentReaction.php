<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

if (!comment_reactions_enabled()) {
    fail("Les réactions aux commentaires ne sont pas encore disponibles.", '../index.php', 400);
}

$comment = db_one('SELECT * FROM comment WHERE id = ?', array(input_int('id')));
$object = $comment ? visible_object($me, $comment['product_id']) : null;

if (!$object) {
    fail("Ce commentaire n'existe pas.", '../index.php', 404);
}

// Champ vide : retirer sa réaction. Sinon, un emoji (le même que l'actuel le retire aussi).
$value = input('emoji');
$emoji = comment_reaction_emoji($value);
if ('' !== trim($value) && '' === $emoji) {
    fail('Choisissez un emoji.', '../index.php', 400);
}

comment_reaction_set($comment['id'], $me['id'], $emoji);

$reactions = comment_reactions_for(array($comment['id']));
$comment['reactions'] = isset($reactions[$comment['id']]) ? $reactions[$comment['id']] : array();
$owner = user_find($object['user_id']);

succeed(array(
    'html' => render('partials/comment_reactions', array('comment' => $comment, 'me' => $me)),
), list_url($owner['code'], '#idea-' . (int) $object['id']));
