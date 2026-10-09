<?php
/*
 * Administration : gemmes gagnées par action (idée, commentaire, réaction…) et prix des habillages,
 * enregistrés dans kdo_setting.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
require_admin();

$back = '../admin.php?tab=gems';
if (!skins_enabled()) {
    fail('Les gemmes ne sont pas disponibles.', $back);
}

// La récompense d'un badge se règle ici, indépendamment de ses conditions d'obtention.
if ('badge' === input('op')) {
    $badge = db_one('SELECT id FROM badge WHERE id = ?', array(input_int('badge_id')));
    if (!$badge) {
        fail('Badge introuvable.', $back, 404);
    }
    db_update('badge', array('gems' => max(0, min(10000, input_int('badge_gems')))), array('id' => (int) $badge['id']));
    unset($_SESSION['kdo_badges_at']);
    succeed(array(), $back, 'Récompense du badge enregistrée.');
}

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
$priceKeys = array_keys(skins());
// Cadres et effets de compte à rebours : champ « frame_ruban » (accessory_price_setting()).
if (accessories_enabled()) {
    foreach (array_keys(accessory_items()) as $id) {
        $priceKeys[] = accessory_price_setting($id);
    }
}
foreach ($priceKeys as $key) {
    if (isset($prices[$key])) {
        setting_save('price_' . $key, max(1, min(100000, (int) $prices[$key])));
    }
}

succeed(array(), $back, 0 < count($prices) ? 'Prix de la boutique enregistrés.' : 'Gemmes par action enregistrées.');
