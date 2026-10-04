<?php
/*
 * Liste privée : nouveau lien d'invitation (l'ancien ne marche plus, les invités gardent l'accès).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$owner = target_owner($me);
$back = list_url($owner['code']);

if (!invites_enabled()) {
    fail("Les invitations ne sont pas encore activées.", $back, 500);
}

list_invite_reset($owner);

succeed(array(), $back, "Nouveau lien d'invitation créé : l'ancien ne marche plus.");
