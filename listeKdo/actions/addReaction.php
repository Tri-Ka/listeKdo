<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$object = visible_object($me, input_int('object'));
$type = input_int('value');
$types = reaction_types();

if (!$object || !isset($types[$type])) {
    fail('Réaction invalide.', '../index.php', 400);
}

reaction_set($object['id'], $me['id'], $type);

$owner = user_find($object['user_id']);
$object['reactions'] = object_reactions($object['id']);
$ctx = array('me' => $me);

succeed(array(
    'summary' => render('partials/reactions', array('object' => $object, 'ctx' => $ctx)),
    'detail' => render('partials/reactors', array('object' => $object)),
), list_url($owner['code'], '#idea-' . (int) $object['id']));
