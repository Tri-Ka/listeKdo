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
foreach (user_children($me['id']) as $child) {
    $lists[] = array('code' => $child['code'], 'nom' => $child['nom'], 'avatar' => avatar_url($child));
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
    'prices' => prices_enabled(),
    'user' => array(
        'nom' => $me['nom'],
        'code' => $me['code'],
        'avatar' => avatar_url($me),
        'theme' => $me['theme'],
    ),
));
