<?php
/*
 * Porter un cadre ou un effet de compte à rebours acheté dans la boutique, ou le retirer :
 * kind = frame ou countdown, item = clé de l'article (vide pour retirer). Sur son propre compte.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();
$back = list_url($me['code']);

if (!accessories_enabled()) {
    fail("Ces articles ne sont pas encore en boutique.", $back);
}
$kinds = accessory_kinds();
$kind = input('kind');
if (!isset($kinds[$kind])) {
    fail('Article inconnu.', $back, 404);
}

$key = accessory_choice($me, $kind, input('item'), $back);

db_update('liste_user', array($kinds[$kind]['column'] => '' !== $key ? $key : null), array('id' => (int) $me['id']));

if ('' === $key) {
    succeed(array(), $back, 'frame' === $kind ? 'Votre photo n\'a plus de cadre.' : 'Le compte à rebours a retrouvé son style classique.');
}
succeed(array(), $back, '« ' . $kinds[$kind]['catalog'][$key]['label'] . ' » ' . ('frame' === $kind ? 'entoure maintenant votre photo.' : 'anime maintenant votre compte à rebours.'));
