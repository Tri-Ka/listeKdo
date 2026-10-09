<?php
if ('1' !== getenv('LISTEKDO_LOCAL_TEST')) exit(1);
require_once dirname(__FILE__) . '/../../listeKdo/lib/bootstrap.php';
$statePath = dirname(__FILE__) . '/out/.gem-tables-fixture';
if ('cleanup' === getenv('GEM_FIXTURE_OP')) {
    if (file_exists($statePath)) {
        $fh = fopen($statePath, 'rb');
        $state = unserialize(fread($fh, filesize($statePath)));
        fclose($fh);
    } else {
        $user = user_find_by_name('Test1gemtableadmin');
        if (!$user) exit(0);
        $state = array('user' => $user['id'], 'settings' => array());
    }
    foreach ($state['settings'] as $name => $row) {
        db_query('DELETE FROM kdo_setting WHERE name = ?', array($name));
        if ($row) db_insert('kdo_setting', $row);
    }
    if (isset($state['reward_badges'])) foreach ($state['reward_badges'] as $row) db_update('badge', array('gems' => $row['gems']), array('id' => $row['id']));
    db_query('DELETE FROM user_badge WHERE badge_id IN (SELECT id FROM badge WHERE code IN (?)) OR user_id = ?', array(array('Test1gemtablebadge137', 'Test1gemtablebadge9'), $state['user']));
    db_query('DELETE FROM badge WHERE code IN (?) OR name = ?', array(array('Test1gemtablebadge137', 'Test1gemtablebadge9'), 'Test1gemtablebadge-new'));
    db_query('DELETE FROM notification WHERE author_id = ?', array($state['user']));
    db_query('DELETE FROM user_visit WHERE user_id = ?', array($state['user']));
    db_query('DELETE FROM liste_user WHERE id = ?', array($state['user']));
    $roleUser = user_find_by_name('Test1gemtablerole');
    if ($roleUser) admin_delete_user($roleUser);
    if (file_exists($statePath)) unlink($statePath);
    echo "Fixtures supprimées et réglages restaurés.\n";
    exit;
}
if (file_exists($statePath) || user_find_by_name('Test1gemtableadmin') || user_find_by_name('Test1gemtablerole')) { echo "Fixtures déjà présentes.\n"; exit(1); }
$state = array('settings' => array(), 'user' => 0, 'reward_badges' => db_all("SELECT id, gems FROM badge WHERE code IN ('visit-1','visit-7','visit-30','trophy-visit-100')"));
$settingNames = array('gem_rewards_version', 'price_pastel', 'price_calligraphie');
foreach (array_keys(gem_actions()) as $key) $settingNames[] = 'gems_' . $key;
foreach ($settingNames as $name) {
    $state['settings'][$name] = db_one('SELECT name, value FROM kdo_setting WHERE name = ?', array($name));
}
$fh = fopen($statePath, 'wb');
if (!$fh) { echo "Impossible de sauvegarder les fixtures.\n"; exit(1); }
$state['user'] = user_create('Test1gemtableadmin', 'test', '', 'birthday');
fwrite($fh, serialize($state));
fclose($fh);
db_update('liste_user', array('role' => 'admin', 'secret_question' => 'test', 'secret_answer' => 'test', 'onboarding_seen_at' => db_now()), array('id' => $state['user']));
user_create('Test1gemtablerole', 'test', '', 'birthday');
foreach (array(137, 9) as $amount) {
    db_insert('badge', array('code' => 'Test1gemtablebadge' . $amount, 'name' => 'Test1gemtablebadge' . $amount, 'description' => 'Fixture locale', 'emoji' => 'B', 'metric' => 'ideas', 'threshold' => 100000, 'kind' => 'badge', 'tier' => 'bronze', 'position' => 10000, 'active' => 0, 'secret' => 0, 'gems' => $amount));
}
echo "Fixtures locales prêtes.\n";
