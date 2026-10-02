<?php
/*
 * Lien court de partage d'une liste (voir short_share_url() dans lib/view.php).
 * Appelé par js/app.js après l'affichage de la page, pour ne pas la ralentir.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

$owner = user_find_by_code(input('user'));

if (!$owner || !can_view(current_user(), $owner)) {
    send_json(array('ok' => false, 'message' => "Cette liste n'existe pas."), 404);
}

send_json(array('ok' => true, 'url' => short_share_url($owner), 'long' => share_url($owner)));
