<?php
if ('1' !== getenv('LISTEKDO_LOCAL_TEST')) exit(1);
require_once dirname(__FILE__) . '/../../listeKdo/lib/bootstrap.php';
$statePath = dirname(__FILE__) . '/out/.gem-rewards-fixture';
$phase = getenv('GEM_TEST_PHASE');
function rewards_test_equal($actual, $expected, $label)
{
    if ($actual !== $expected) {
        echo 'FAIL ' . $label . ': ' . kdo_json($actual) . ' != ' . kdo_json($expected) . "\n";
        exit(1);
    }
}
function rewards_test_actions($id)
{
    $gems = gems_of($id);
    return $gems['actions'];
}
function rewards_test_count($id, $key)
{
    $counts = gem_action_counts($id);
    return $counts[$key];
}
if ('setup' === $phase) {
    if (file_exists($statePath)) { echo "Fixtures déjà présentes.\n"; exit(1); }
    foreach (array('Author', 'Owner', 'Giver', 'Partner') as $suffix) {
        if (user_find_by_name('Test1gemrewards' . $suffix)) { echo "Compte de test déjà présent.\n"; exit(1); }
    }
    $state = array('users' => array(), 'settings' => array(), 'badges' => db_all("SELECT id, gems FROM badge WHERE code IN ('visit-1','visit-7','visit-30','trophy-visit-100')"));
    $names = array('gem_rewards_version');
    foreach (array_keys(gem_actions()) as $key) $names[] = 'gems_' . $key;
    $saved = db_index_by(db_all('SELECT name, value FROM kdo_setting WHERE name IN (?)', array($names)), 'name');
    foreach ($names as $name) $state['settings'][$name] = isset($saved[$name]) ? $saved[$name] : null;
    $fh = fopen($statePath, 'wb');
    fwrite($fh, serialize($state)); fclose($fh);
    foreach (array('Author', 'Owner', 'Giver', 'Partner') as $suffix) {
        $state['users'][$suffix] = user_create('Test1gemrewards' . $suffix, 'test', '', 'birthday');
        $fh = fopen($statePath, 'wb'); fwrite($fh, serialize($state)); fclose($fh);
    }
    echo "Fixtures locales prêtes.\n";
    exit;
}
if (!file_exists($statePath)) { echo "Fixtures absentes.\n"; exit(1); }
$fh = fopen($statePath, 'rb'); $state = unserialize(fread($fh, filesize($statePath))); fclose($fh);
if ('cleanup' === $phase) {
    foreach ($state['users'] as $id) { $user = user_find($id); if ($user) admin_delete_user($user); }
    db_query('DELETE FROM kdo_setting WHERE name IN (?)', array(array_keys($state['settings'])));
    foreach ($state['settings'] as $row) if ($row) db_insert('kdo_setting', $row);
    foreach ($state['badges'] as $row) db_update('badge', array('gems' => $row['gems']), array('id' => $row['id']));
    unlink($statePath);
    echo "Fixtures supprimées et barème restauré.\n";
    exit;
}
if ('persist' === $phase) {
    $persisted = gem_action_rates();
    rewards_test_equal($persisted['ideas'], 2, 'réglage admin à 2 conservé après la migration');
    exit;
}
$id = $state['users']['Author'];
$owner = $state['users']['Owner'];
$giver = $state['users']['Giver'];
$partner = $state['users']['Partner'];
// Remettre les anciens défauts pour exercer la migration, avec un montant personnalisé et un gain désactivé.
db_query('DELETE FROM kdo_setting WHERE name IN (?)', array(array_keys($state['settings'])));
foreach (array('ideas' => 2, 'comments' => 17, 'reactions' => 0, 'gifts' => 3, 'groups' => 3, 'friends' => 1) as $key => $value) setting_save('gems_' . $key, $value);
$rates = gem_action_rates();
rewards_test_equal($rates['ideas'], 40, 'idée à 40');
rewards_test_equal($rates['gifts'], 60, 'réservation à 60');
rewards_test_equal($rates['comments'], 17, 'montant personnalisé conservé');
rewards_test_equal($rates['reactions'], 0, 'gain désactivé conservé');
rewards_test_equal(count($rates), 11, 'cinq nouvelles actions');
// SQL et PHP doivent donner le même montant pour toutes les raretés de badge et les montants fixes.
$checks = array(); $expected = array();
foreach (array('badge', 'trophy') as $kind) {
    foreach (array('bronze', 'silver', 'gold', 'legend') as $tier) {
        $badge = array('kind' => $kind, 'tier' => $tier, 'gems' => 0);
        $checks[] = db_build('SELECT ? AS kind, ? AS tier, ? AS gems', array($kind, $tier, 0));
        $expected[] = badge_gems($badge);
    }
}
$checks[] = "SELECT 'trophy' AS kind, 'legend' AS tier, 151 AS gems";
$expected[] = 151;
$rows = db_all('SELECT ' . badge_gems_sql() . ' AS amount FROM (' . implode(' UNION ALL ', $checks) . ') b');
foreach ($rows as $i => $row) rewards_test_equal((int) $row['amount'], $expected[$i], 'badge PHP/SQL ' . $i);
rewards_test_equal(badge_default_gems('badge', 'bronze'), 40, 'badge bronze à 40');
rewards_test_equal(badge_default_gems('trophy', 'legend'), 1600, 'trophée légendaire à 1600');

$own = db_insert('liste_noel', array('nom' => 'Test1gemrewards own', 'user_id' => $id, 'created_at' => db_now()));
rewards_test_equal(rewards_test_actions($id), 40, 'une idée rapporte 40');
$suggestion = db_insert('liste_noel', array('nom' => 'Test1gemrewards suggestion', 'user_id' => $owner, 'suggested_by' => $id, 'created_at' => db_now()));
$beforeOwner = gems_of($owner);
rewards_test_equal(rewards_test_count($id, 'suggestions'), 1, 'suggestion attribuée à son auteur');
rewards_test_equal(rewards_test_actions($owner), 0, 'suggestion cachée ne rapporte pas au propriétaire');
db_update('liste_noel', array('gifted_by' => $id), array('id' => $suggestion));
rewards_test_equal(rewards_test_count($id, 'suggestions_gifted'), 0, 'sa propre réservation ne déclenche pas le bonus');
db_update('liste_noel', array('gifted_by' => $giver), array('id' => $suggestion));
rewards_test_equal(rewards_test_count($id, 'suggestions_gifted'), 1, 'réservation par quelqu’un d’autre');
rewards_test_equal(rewards_test_actions($id), 180, '40 idée + 40 suggestion + 100 suggestion réservée');
rewards_test_equal(rewards_test_actions($owner), $beforeOwner['actions'], 'solde du propriétaire inchangé');
$dbObject = object_find($suggestion);
object_set_received($dbObject, true);
rewards_test_equal(rewards_test_count($giver, 'gifts_received'), 1, 'donneur récompensé à réception');
rewards_test_equal(rewards_test_actions($giver), 160, '60 réservation + 100 réception');
object_set_received(object_find($suggestion), true);
rewards_test_equal(rewards_test_actions($giver), 160, 'réception répétée sans doublon');
object_set_received(object_find($suggestion), false);
rewards_test_equal(rewards_test_count($giver, 'gifts_received'), 0, 'annulation retire le bonus');
object_set_received(object_find($suggestion), true);
rewards_test_equal(rewards_test_actions($giver), 160, 'aller-retour reçu sans gain supplémentaire');

$collection = db_insert('liste_noel', array('nom' => 'Test1gemrewards collection', 'user_id' => $owner, 'suggested_by' => $id, 'created_at' => db_now()));
$first = db_insert('liste_item', array('product_id' => $collection, 'nom' => 'Premier', 'position' => 0, 'gifted_by' => $giver, 'received_at' => db_now()));
$second = db_insert('liste_item', array('product_id' => $collection, 'nom' => 'Second', 'position' => 1, 'gifted_by' => $giver));
rewards_test_equal(rewards_test_count($id, 'suggestions_gifted'), 2, 'une réservation par suggestion, même avec plusieurs éléments');
rewards_test_equal(rewards_test_count($giver, 'gifts_received'), 2, 'seul l’élément reçu rapporte');
object_set_received(object_find($collection), true);
rewards_test_equal(rewards_test_count($giver, 'gifts_received'), 3, 'collection reçue sans compter aussi son parent');

$group = db_insert('liste_noel', array('nom' => 'Test1gemrewards group', 'user_id' => $owner, 'suggested_by' => $id, 'price' => 100, 'created_at' => db_now()));
participation_set($group, $giver, 40);
participation_set($group, $partner, 59);
rewards_test_equal(rewards_test_count($giver, 'groups_completed'), 0, 'cagnotte incomplète');
participation_set($group, $partner, 60);
rewards_test_equal(rewards_test_count($giver, 'groups_completed'), 1, 'cagnotte complète pour le premier participant');
rewards_test_equal(rewards_test_count($partner, 'groups_completed'), 1, 'cagnotte complète pour le second participant');
rewards_test_equal(rewards_test_count($id, 'suggestions_gifted'), 3, 'participation réserve une suggestion une seule fois');
object_set_received(object_find($group), true);
rewards_test_equal(rewards_test_count($giver, 'gifts_received'), 4, 'cadeau commun reçu récompense aussi ses participants');
rewards_test_equal(rewards_test_count($partner, 'gifts_received'), 1, 'second participant récompensé');
rewards_test_equal(rewards_test_actions($owner), 0, 'tous les bonus cachés restent exclus du propriétaire');

rewards_test_equal(rewards_test_count($id, 'profile_complete'), 0, 'profil incomplet');
db_update('liste_user', array('pictureFileUrl' => 'https://example.org/avatar.png', 'secret_question' => 'Question', 'message' => 'Merci', 'event_date' => '2000-01-01', 'list_title' => 'Ma liste'), array('id' => $id));
rewards_test_equal(rewards_test_count($id, 'profile_complete'), 1, 'profil complet compte une fois');
$complete = gems_of($id);
db_update('liste_user', array('list_title' => 'Mon autre titre'), array('id' => $id));
rewards_test_equal(rewards_test_actions($id), $complete['actions'], 'modifier un profil complet ne redonne pas de bonus');
$metrics = badge_metric_values(user_find($id));
rewards_test_equal($metrics['profile_complete'], 5, 'critères partagés avec les badges');
rewards_test_equal($metrics['suggestions_gifted'], 3, 'condition partagée avec les badges de suggestion');
setting_save('gems_ideas', 2);
echo "OK : nouveau barème, montants personnalisés, badges PHP/SQL, cinq bonus, confidentialité des suggestions, collections et cagnottes sans doublon.\n";
?>
