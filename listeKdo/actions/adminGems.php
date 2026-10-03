<?php
/*
 * Administration : gemmes gagnées par action (idée, commentaire, réaction…), enregistrées dans kdo_setting.
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

succeed(array(), $back, 'Gemmes par action enregistrées.');
