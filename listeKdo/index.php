<?php
require_once 'lib/bootstrap.php';

$me = current_user();
$page = input('page');
$isHowItWorks = 'comment-ca-marche' === $page;
$code = input('user');
$owner = null;

if ('' !== $code) {
    $owner = user_find_by_code($code);
} elseif ($me) {
    $owner = $me;
}

// Tous les amis pour les notifications (filtrées dans notifications_where()), seulement les listes visibles à l'écran.
$allFriends = $me ? user_friends($me['id']) : array();
$friends = friends_by_event(visible_lists($me, $allFriends));
$isOwner = $me && $owner && (int) $me['id'] === (int) $owner['id'];
$isFriend = false;

if ($me && $owner && !$isOwner) {
    foreach ($friends as $friend) {
        if ((int) $friend['id'] === (int) $owner['id']) {
            $isFriend = true;
        }
    }
}

$ctx = array(
    'me' => $me,
    'owner' => $owner,
    'isOwner' => $isOwner,
    'isFriend' => $isFriend,
    // Modifier la liste : la sienne, ou celle d'un enfant qu'on gère.
    'canEdit' => can_manage($me, $owner),
    // Liste privée : seuls ceux qui la gèrent la voient.
    'private' => is_private_list($owner),
    'canView' => can_view($me, $owner),
    // Voir qui offre quoi : tous les utilisateurs connectés sauf le propriétaire lui-même
    // (le parent d'un enfant le voit, pour coordonner les cadeaux).
    'canGift' => $me && $owner && !$isOwner,
    'children' => $me ? user_children($me['id']) : array(),
    'ownerChildren' => $owner ? visible_lists($me, user_children($owner['id'])) : array(),
);

// Les idées reçues (archivées) ne sont visibles que par ceux qui gèrent la liste.
$objects = array();
if ($owner && $ctx['canView']) {
    foreach (objects_for_user($owner['id']) as $id => $object) {
        if (!$object['received'] || $ctx['canEdit']) {
            $objects[$id] = $object;
        }
    }
}

// Rappels « l'anniversaire de … approche » (avant de charger les notifications).
if ($me) {
    notifications_create_event_reminders($friends);
}

// Badges : ceux de la personne connectée sont mis à jour, les nouveaux sont fêtés une fois.
// La vitrine de la liste affichée montre la progression seulement à ceux qui la gèrent.
badges_refresh($me);
$badges = array(
    'new' => badges_take_new($me),
    'count' => $owner && $ctx['canView'] ? badges_count($owner) : 0,
    'showcase' => $owner && $ctx['canView'] ? badges_showcase($owner, $ctx['canEdit']) : array(),
);

// Boutique : gemmes de la personne connectée et habillages qu'elle possède.
$shop = $me && skins_enabled() ? array('gems' => gems_of($me['id']), 'owned' => skins_owned($me['id'])) : null;
// Solde vu à la page précédente : s'il a augmenté, la pastille l'anime (js/app.js).
if ($shop) {
    $shop['from'] = isset($_SESSION['kdo_gems_seen']) ? (int) $_SESSION['kdo_gems_seen'] : $shop['gems']['balance'];
    $_SESSION['kdo_gems_seen'] = $shop['gems']['balance'];
}

echo render('page', array(
    'ctx' => $ctx,
    'me' => $me,
    'owner' => $owner,
    'friends' => $friends,
    'objects' => $objects,
    // Une notification de plus que la page, pour savoir s'il faut un bouton « Voir plus ».
    'notifications' => $me ? notifications_for($me, $allFriends, 0, NOTIFICATIONS_PER_PAGE + 1) : array(),
    'newNotifications' => $me ? notifications_new_count($me, $allFriends) : 0,
    'myGifts' => $me ? my_gifts($me) : array(),
    'theme' => theme_of($owner),
    'badges' => $badges,
    'shop' => $shop,
    'isHowItWorks' => $isHowItWorks,
    // La visite est mémorisée par compte, pas seulement dans ce navigateur.
    'showOnboarding' => $me && onboarding_available() && empty($me['onboarding_seen_at']),
    'flash' => flash_take(),
));
