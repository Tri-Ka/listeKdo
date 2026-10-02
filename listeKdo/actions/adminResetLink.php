<?php
/*
 * Lien de secours à envoyer à quelqu'un qui a oublié son mot de passe (usage unique, 48 h).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_admin();

$user = user_find(input_int('id'));

if (!$user || !reset_links_enabled()) {
    fail('Utilisateur inconnu.', '../admin.php', 404);
}

if (admin_is_child_account($user)) {
    fail("Cette liste secondaire n'a pas de mot de passe : elle est gérée par ses gestionnaires.", '../admin.php');
}

$link = reset_link_create($user);

if (!$link) {
    fail("Le lien n'a pas pu être créé.", '../admin.php', 500);
}

succeed(array(
    'name' => $user['nom'],
    'url' => $link['short'],
    'expires' => date('d/m/Y à H:i', $link['expires']),
), '../admin.php', 'Lien de secours pour ' . $user['nom'] . ' : ' . $link['short']);
