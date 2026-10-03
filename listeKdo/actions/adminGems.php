<?php
/*
 * Administration : gemmes gagnées par action (idée, commentaire, réaction…) et prix des habillages,
 * enregistrés dans kdo_setting.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
require_admin();

$back = '../admin.php?tab=badges';
if (!settings_enabled()) {
    fail("La table des réglages n'existe pas (sql/2026-10-03-gemmes-actions.sql).", $back);
}

$posted = isset($_POST['rate']) && is_array($_POST['rate']) ? $_POST['rate'] : array();
foreach (gem_actions() as $key => $info) {
    if (isset($posted[$key])) {
        setting_save('gems_' . $key, max(0, min(1000, (int) $posted[$key])));
    }
}

// Parrainage
if (isset($_POST['referral']) && is_array($_POST['referral'])) {
    foreach (array('sponsor' => 'gems_referral', 'welcome' => 'gems_welcome') as $key => $name) {
        if (isset($_POST['referral'][$key])) {
            setting_save($name, max(0, min(100000, (int) $_POST['referral'][$key])));
        }
    }
}

// Prix des habillages (même formulaire ou formulaire « Prix des habillages »).
$prices = isset($_POST['price']) && is_array($_POST['price']) ? $_POST['price'] : array();
foreach (skins() as $key => $skin) {
    if (isset($prices[$key])) {
        setting_save('price_' . $key, max(1, min(100000, (int) $prices[$key])));
    }
}

succeed(array(), $back, 0 < count($prices) ? 'Prix des habillages enregistrés.' : 'Gemmes par action enregistrées.');
