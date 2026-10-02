<?php
/*
 * Admin : parents d'une liste. Une liste avec au moins un parent est une liste d'enfant
 * (table liste_manager) ; retirer le dernier parent en refait une liste normale.
 * op = list | add | remove. Répond avec la liste des parents à jour.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_admin();

$child = user_find(input_int('id'));

if (!$child || !children_enabled()) {
    fail('Liste inconnue.', '../admin.php?tab=lists', 404);
}

$op = input('op');
$message = '';

if ('add' === $op) {
    $parent = user_find(input_int('parent'));

    if (!$parent) {
        fail('Choisissez un parent dans la liste.', '../admin.php?tab=lists', 404);
    }
    if ((int) $parent['id'] === (int) $child['id']) {
        fail('Une liste ne peut pas être son propre parent.', '../admin.php?tab=lists');
    }
    if (admin_is_child_account($parent) || is_child_list($parent)) {
        fail($parent['nom'] . " est déjà une liste d'enfant : elle ne peut pas être parent.", '../admin.php?tab=lists');
    }
    if (0 < count(user_children($child['id']))) {
        fail($child['nom'] . " gère déjà des listes d'enfants : elle ne peut pas devenir une liste d'enfant.", '../admin.php?tab=lists');
    }

    manager_add($child['id'], $parent['id']);
    // Comme depuis la liste de l'enfant : le parent apparaît dans ses amis.
    friend_add($child, $parent);
    $message = $parent['nom'] . ' est maintenant parent de ' . $child['nom'] . '.';
} elseif ('remove' === $op) {
    manager_remove($child['id'], input_int('parent'));
    $message = 0 < count(child_managers($child['id']))
        ? 'Parent retiré.'
        : $child['nom'] . " n'est plus une liste d'enfant.";
}

$managers = array();
foreach (child_managers($child['id']) as $manager) {
    $managers[] = array('id' => (int) $manager['id'], 'nom' => $manager['nom'], 'avatar' => avatar_url($manager));
}

succeed(array('name' => $child['nom'], 'managers' => $managers), '../admin.php?tab=lists', $message);
