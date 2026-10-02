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
    'parentOptions' => admin_parent_options(),
    'flash' => flash_take(),
));
