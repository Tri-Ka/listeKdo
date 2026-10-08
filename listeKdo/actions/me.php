<?php
/*
 * Utilisateur connecté et jeton CSRF, pour l'extension Chrome.
 * Lecture seule. Une page d'un autre site ne peut pas lire cette réponse (pas d'en-têtes CORS) ;
 * seule l'extension, autorisée sur ce domaine, y a accès.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

$me = current_user();

if (!$me) {
    send_json(array('ok' => false, 'message' => 'Non connecté.'), 401);
}

// Listes où l'extension peut ajouter une idée : la sienne, puis celles des enfants gérés.
$lists = array(array('code' => $me['code'], 'nom' => 'Ma liste', 'avatar' => avatar_url($me)));
$listIds = array((int) $me['id']);
$listCodes = array((int) $me['id'] => $me['code']);
foreach (user_children($me['id']) as $child) {
    $lists[] = array('code' => $child['code'], 'nom' => $child['nom'], 'avatar' => avatar_url($child));
    $listIds[] = (int) $child['id'];
    $listCodes[$child['id']] = $child['code'];
}

// Une seule requête pour toutes les collections des listes gérées, sans réservation.
$collections = array();
if (items_enabled()) {
    $sql = 'SELECT n.id, n.nom, n.user_id FROM liste_noel n WHERE n.user_id IN (?)' . own_ideas_sql('n.')
        . (received_enabled() ? ' AND n.received_at IS NULL' : '')
        . ' AND EXISTS (SELECT 1 FROM liste_item i WHERE i.product_id = n.id'
        . (items_received_enabled() ? ' AND i.received_at IS NULL' : '') . ') ORDER BY n.nom ASC, n.id ASC';
    foreach (db_all($sql, array($listIds)) as $collection) {
        $collections[] = array('id' => (int) $collection['id'], 'nom' => legacy_text($collection['nom']), 'owner' => $listCodes[$collection['user_id']]);
    }
}

// Amis à qui suggérer une idée (ils ne la verront pas, leurs autres amis oui) : voir can_suggest().
$friends = array();
foreach (suggestion_targets($me) as $friend) {
    $friends[] = array('code' => $friend['code'], 'nom' => $friend['nom'], 'avatar' => avatar_url($friend));
}

send_json(array(
    'ok' => true,
    'token' => csrf_token(),
    'lists' => $lists,
    'friends' => $friends,
    'collections' => $collections,
    'prices' => prices_enabled(),
    'user' => array(
        'nom' => $me['nom'],
        'code' => $me['code'],
        'avatar' => avatar_url($me),
        'theme' => $me['theme'],
    ),
));
