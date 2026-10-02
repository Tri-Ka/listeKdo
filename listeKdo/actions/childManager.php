<?php
/*
 * Parents d'une liste d'enfant : ajouter un parent (parmi ses amis) ou en retirer un.
 * Un enfant garde toujours au moins un parent.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

$child = target_owner($me);
$back = list_url($child['code']);

if ((int) $child['id'] === (int) $me['id'] || !is_child_list($child)) {
    fail("Ce n'est pas une liste secondaire.", $back);
}

if ('' !== input('manager_remove')) {
    $managers = child_managers($child['id']);
    if (count($managers) < 2) {
        fail('La liste doit garder au moins un gestionnaire.', $back);
    }
    manager_remove($child['id'], input_int('manager_remove'));
    // On s'est retiré soi-même : retour à sa propre liste.
    $back = input_int('manager_remove') === (int) $me['id'] ? list_url($me['code']) : $back;
    succeed(array(), $back, 'Gestionnaire retiré.');
}

$parent = user_find(input_int('manager_id'));
if (!$parent || !user_has_friend($me['id'], $parent['code'])) {
    fail('Choisissez un gestionnaire parmi vos amis.', $back);
}

manager_add($child['id'], $parent['id']);
friend_add($child, $parent);

succeed(array(), $back, $parent['nom'] . ' peut maintenant gérer « ' . $child['nom'] . ' ».');
