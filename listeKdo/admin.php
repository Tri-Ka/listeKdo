<?php
/*
 * Administration : utilisateurs et listes (réservé aux admins).
 * Avec ?partial=1, ne renvoie que le tableau (rechargé en arrière-plan par js/admin.js).
 */
require_once 'lib/bootstrap.php';

$me = current_user();

if (!is_admin($me)) {
    flash($me ? 'Cette page est réservée aux administrateurs.' : 'Connectez-vous pour accéder à cette page.');
    redirect($me ? 'index.php?user=' . rawurlencode($me['code']) : 'index.php');
}

// Onglet « Badges » : à la première ouverture, les badges par défaut sont installés.
$tab = input('tab');
$badgesAdmin = null;
if ('badges' === $tab && badges_enabled()) {
    if (0 === count(badges_all(false))) {
        badges_install();
    }
    $badgesAdmin = admin_badges();
}

$table = admin_table(admin_table_params());

if ('1' === input('partial')) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo render('partials/admin_table', array('table' => $table, 'me' => $me));
    exit;
}

echo render('admin', array(
    'me' => $me,
    'table' => $table,
    'stats' => admin_stats(),
    'insights' => admin_insights(),
    'parentOptions' => admin_parent_options(),
    'badgesAdmin' => $badgesAdmin,
    'flash' => flash_take(),
));
