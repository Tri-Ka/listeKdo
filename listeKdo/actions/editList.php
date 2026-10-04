<?php
/*
 * Paramètres d'une liste (la sienne ou une liste secondaire qu'on gère) :
 * titre, type de liste (thème), habillage, effet du compte à rebours, date de l'événement, liste privée.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$owner = target_owner($me);
$back = list_url($owner['code']);

$changes = array('theme' => valid_theme(input('theme'), $owner['theme']));

if (list_title_enabled() && isset($_POST['list_title'])) {
    $title = trim(input('list_title'));
    if (strlen($title) > 120) {
        fail('Le titre est trop long (120 caractères maximum).', $back);
    }
    $changes['list_title'] = '' !== $title ? $title : null;
}

if (event_dates_enabled() && isset($_POST['event_date'])) {
    $changes['event_date'] = valid_date(input('event_date'));
}

if (private_enabled() && isset($_POST['is_private'])) {
    $changes['is_private'] = '1' === input('is_private') ? 1 : 0;
}

// Habillage (boutique) : un de ceux achetés par la personne connectée, ou le classique.
if (skins_enabled() && isset($_POST['skin'])) {
    $key = skin_choice($me, input('skin'), $changes['theme'], $back);
    $changes['skin'] = '' !== $key ? $key : null;
}

// Effet du compte à rebours (boutique) : un de ceux achetés par la personne connectée, ou le classique.
if (accessories_enabled() && isset($_POST['countdown_fx'])) {
    $key = accessory_choice($me, 'countdown', input('countdown_fx'), $back);
    $changes['countdown_fx'] = '' !== $key ? $key : null;
}

db_update('liste_user', $changes, array('id' => (int) $owner['id']));

// Liste privée : amis choisis qui la voient quand même (cases à cocher, aucune cochée = personne).
if (viewers_enabled() && isset($_POST['viewers_sent'])) {
    $viewers = isset($_POST['viewers']) && is_array($_POST['viewers']) ? $_POST['viewers'] : array();
    list_viewers_set($owner, $viewers);
}

succeed(array(), $back, 'Liste mise à jour.');
