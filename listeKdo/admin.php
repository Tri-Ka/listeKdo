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

// Chaque partie charge uniquement les données dont elle a besoin.
$sections = array(
    'analytics' => array('label' => 'Analyse', 'icon' => 'chart-column', 'description' => 'Suivez l’activité de la communauté et les données du site.'),
    'users' => array('label' => 'Utilisateurs', 'icon' => 'users', 'description' => 'Gérez les comptes, les rôles et les accès.'),
    'lists' => array('label' => 'Listes', 'icon' => 'list', 'description' => 'Retrouvez les listes et leurs gestionnaires.'),
);
if (badges_enabled()) {
    $sections['badges'] = array('label' => 'Badges', 'icon' => 'crown', 'description' => 'Créez les badges et les trophées, puis définissez leurs conditions d’obtention.');
}
if (skins_enabled()) {
    $sections['gems'] = array('label' => 'Gemmes', 'icon' => '', 'description' => 'Réglez les gemmes gagnées, les récompenses des badges et les prix de la boutique.');
}
$sections['settings'] = array('label' => 'Réglages', 'icon' => 'gear', 'description' => 'Configurez les services utilisés par le site.');
$tab = input('tab');
if (!isset($sections[$tab])) {
    $tab = 'users';
}
$onTable = 'users' === $tab || 'lists' === $tab;
$table = $onTable ? admin_table(admin_table_params()) : null;

if ('1' === input('partial')) {
    if (!$onTable) {
        fail('Cette partie ne contient pas de tableau.', 'admin.php', 400);
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo render('partials/admin_table', array('table' => $table, 'me' => $me));
    exit;
}

// À la première ouverture des badges ou des gemmes, installer les badges par défaut.
$badgesAdmin = null;
if (('badges' === $tab || 'gems' === $tab) && badges_enabled()) {
    if (0 === count(badges_all(false))) {
        badges_install();
    }
    $badgesAdmin = admin_badges();
}

echo render('admin', array(
    'me' => $me,
    'table' => $table,
    'tab' => $tab,
    'sections' => $sections,
    'stats' => 'analytics' === $tab ? admin_stats() : null,
    'insights' => 'analytics' === $tab ? admin_insights() : null,
    'parentOptions' => $onTable ? admin_parent_options() : array(),
    'badgesAdmin' => $badgesAdmin,
    'flash' => flash_take(),
));
