<?php
/*
 * Lien court TinyURL d'un lien à partager (voir short_link() dans lib/view.php).
 * Appelé par js/app.js après l'affichage de la page ([data-short]), pour ne pas la ralentir.
 * Le lien long est toujours fabriqué ici : on ne raccourcit jamais une adresse envoyée par le navigateur.
 *   type=list     : lien de la liste (?user=CODE), si on peut la voir ;
 *   type=referral : lien de parrainage de la personne connectée ;
 *   type=invite   : lien d'invitation d'une liste privée (?user=CODE), pour ceux qui la gèrent.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

$me = current_user();
$type = input('type');
$long = '';

if ('referral' === $type) {
    if ($me && referral_enabled()) {
        $long = referral_url($me);
    }
} else {
    $owner = user_find_by_code(input('user'));
    if ('invite' === $type) {
        if ($owner && invites_enabled() && can_manage($me, $owner)) {
            $long = list_invite_url($owner);
        }
    } elseif ($owner && can_view($me, $owner)) {
        $long = share_url($owner);
    }
}

if ('' === $long) {
    send_json(array('ok' => false, 'message' => "Ce lien n'existe pas."), 404);
}

send_json(array('ok' => true, 'url' => short_link($long), 'long' => $long));
