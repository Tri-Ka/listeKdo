<?php
/*
 * Solde de gemmes de la personne connectée (lecture seule), interrogé par js/app.js après une action
 * en arrière-plan (réaction, commentaire, don…) pour animer la pastille quand il augmente.
 * Les badges du moment sont mis à jour d'abord (une action peut en débloquer un).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

$me = current_user();
if (!$me || !skins_enabled()) {
    send_json(array('ok' => false), 401);
}

unset($_SESSION['kdo_badges_at']);
badges_refresh($me);
$gems = gems_of($me['id']);
$_SESSION['kdo_gems_seen'] = $gems['balance'];

send_json(array('ok' => true, 'balance' => $gems['balance']));
