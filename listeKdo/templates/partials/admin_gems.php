<?php
/* Administration : tableaux des gains, récompenses et prix, avec édition en modale. */
$gemTables = array();
$canSaveSettings = settings_enabled();
$rates = gem_action_rates();
$actionRows = array();
foreach (gem_actions() as $key => $info) {
    $actionRows[] = array('label' => $info[0], 'category' => 'Action', 'note' => '', 'value' => $rates[$key],
        'default' => $info[1], 'min' => 0, 'max' => 1000, 'name' => 'rate[' . $key . ']', 'hidden' => array(), 'editable' => $canSaveSettings);
}
if (referral_enabled()) {
    $rewards = referral_rewards();
    foreach (array('sponsor' => array('Parrain (filleul actif)', 200), 'welcome' => array('Bienvenue (filleul)', 50)) as $key => $info) {
        $actionRows[] = array('label' => $info[0], 'category' => 'Parrainage', 'note' => '', 'value' => $rewards[$key],
            'default' => $info[1], 'min' => 0, 'max' => 100000, 'name' => 'referral[' . $key . ']', 'hidden' => array(), 'editable' => $canSaveSettings);
    }
}
$gemTables['actions'] = array('title' => 'Gemmes par action', 'hint' => 'Ces gains s’ajoutent aux récompenses des badges et s’appliquent aux actions déjà réalisées. Chaque élément compte une fois ; les annulations et suppressions retirent les gains correspondants.', 'rows' => $actionRows);

$badgeRows = array();
$tiers = badge_tiers();
foreach ($badges as $badge) {
    $badgeRows[] = array('label' => $badge['emoji'] . ' ' . $badge['name'], 'category' => 'trophy' === $badge['kind'] ? 'Trophée' : 'Badge',
        'note' => $tiers[$badge['tier']] . (!$badge['active'] ? ' · Désactivé' : ''), 'value' => $badge['gems'],
        'default' => badge_default_gems($badge['kind'], $badge['tier']), 'min' => 0, 'max' => 10000,
        'name' => 'badge_gems', 'hidden' => array('op' => 'badge', 'badge_id' => $badge['id']), 'editable' => true);
}
$gemTables['badges'] = array('title' => 'Récompenses des badges', 'hint' => '0 = récompense automatique selon le type et le niveau. Un montant personnalisé s’applique aussi aux badges déjà obtenus.', 'rows' => $badgeRows);

$priceRows = array();
$previewThemes = themes();
$previewTheme = isset($previewThemes[$me['theme']]) ? $me['theme'] : 'birthday';
foreach (skins() as $key => $skin) {
    $skinTheme = in_array($previewTheme, $skin['themes']) ? $previewTheme : $skin['themes'][0];
    $base = $previewThemes[$skinTheme];
    $base['key'] = $skinTheme;
    $look = skin_apply($base, array('skin' => $key . '/' . $skinTheme));
    $preview = array('kind' => 'skin', 'title' => 'img/' . $look['dir'] . '/title.png',
        'from' => $skin['colors']['hero-from'], 'to' => $skin['colors']['hero-to']);
    $priceRows[] = array('label' => $skin['label'], 'category' => 'Habillage', 'note' => '', 'rarity' => $skin['rarity'],
        'value' => skin_price($key, $skin['price']), 'default' => $skin['price'], 'min' => 1, 'max' => 100000,
        'name' => 'price[' . $key . ']', 'hidden' => array(), 'editable' => $canSaveSettings, 'preview' => $preview);
}
if (accessories_enabled()) {
    $accessoryKinds = accessory_kinds();
    foreach (accessory_items() as $id => $item) {
        $priceRows[] = array('label' => $item['label'], 'category' => $accessoryKinds[$item['kind']]['label'], 'note' => '', 'rarity' => $item['rarity'],
            'value' => $item['price'], 'default' => $item['default_price'], 'min' => 1, 'max' => 100000,
            'name' => 'price[' . accessory_price_setting($id) . ']', 'hidden' => array(), 'editable' => $canSaveSettings,
            'preview' => array('kind' => $item['kind'], 'key' => $item['key']));
    }
}
$gemTables['shop'] = array('title' => 'Prix de la boutique', 'hint' => 'Prix en gemmes, par article. Le prix payé pour les achats déjà faits est conservé.', 'rows' => $priceRows);
?>
<div class="admin-gems" data-gem-admin>
    <nav class="dt__filters gem-tabs" aria-label="Gestion des gemmes">
        <?php foreach ($gemTables as $key => $gemTable) : ?>
            <a class="dt__chip" href="#gem-<?php echo e($key); ?>" data-gem-section="<?php echo e($key); ?>"><?php echo e($gemTable['title']); ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if (!$canSaveSettings) : ?>
        <p class="gem-rates__warning"><?php echo icon('triangle-exclamation'); ?> Pour modifier les gains et les prix, exécutez <code>sql/2026-10-03-gemmes-actions.sql</code> dans phpMyAdmin.</p>
    <?php endif; ?>
    <?php foreach ($gemTables as $key => $gemTable) : ?>
        <?php echo render('partials/admin_gem_table', array('key' => $key, 'gemTable' => $gemTable, 'me' => $me)); ?>
    <?php endforeach; ?>
    <dialog class="modal modal--small" id="gem-edit-dialog" aria-labelledby="gem-edit-title">
        <form method="post" action="actions/adminGems.php" data-gem-form>
            <?php echo csrf_field(); ?>
            <header class="modal__header">
                <h2 id="gem-edit-title">Modifier le montant</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body">
                <strong data-gem-label></strong>
                <label class="badge-admin__field"><span>Gemmes</span><input type="number" name="amount" min="0" max="100000" required></label>
                <p class="admin__hint" data-gem-hint></p>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--light" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </footer>
        </form>
    </dialog>
</div>
