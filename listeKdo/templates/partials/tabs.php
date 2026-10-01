<?php
$counts = array('all' => 0, 'available' => 0, 'gifted' => 0, 'favorite' => 0, 'received' => 0);
$hasPrices = false;
foreach ($objects as $object) {
    if ($object['received']) {
        $counts['received']++;
        continue;
    }
    $counts['all']++;
    $counts[$object['complete'] ? 'gifted' : 'available']++;
    if ($object['favorite']) {
        $counts['favorite']++;
    }
    if (null !== $object['price']) {
        $hasPrices = true;
    }
}
?>
<nav class="tabs" aria-label="Filtrer les idées" data-tabs>
    <button type="button" class="tabs__tab" aria-pressed="true" data-filter="all"><?php echo icon('gift'); ?> Toutes <span data-count="all">(<?php echo $counts['all']; ?>)</span></button>
    <?php if ($ctx['canGift']) : ?>
        <button type="button" class="tabs__tab gift-only" aria-pressed="false" data-filter="available"><?php echo icon('bag-shopping'); ?> À offrir <span data-count="available">(<?php echo $counts['available']; ?>)</span></button>
        <button type="button" class="tabs__tab gift-only" aria-pressed="false" data-filter="gifted"><?php echo icon('circle-check'); ?> Déjà offertes <span data-count="gifted">(<?php echo $counts['gifted']; ?>)</span></button>
    <?php endif; ?>
    <button type="button" class="tabs__tab tabs__tab--heart" aria-pressed="false" data-filter="favorite"><?php echo icon('heart'); ?> Coups de cœur <span data-count="favorite">(<?php echo $counts['favorite']; ?>)</span></button>
    <?php if ($ctx['canEdit'] && received_enabled()) : ?>
        <button type="button" class="tabs__tab" aria-pressed="false" data-filter="received"<?php echo 0 === $counts['received'] ? ' hidden' : ''; ?>><?php echo icon('box-archive'); ?> Reçus <span data-count="received">(<?php echo $counts['received']; ?>)</span></button>
    <?php endif; ?>
</nav>

<?php // Sur mobile : menu de sélection à la place des onglets (synchronisé par js/app.js). ?>
<label class="tabs-select">
    <span class="sr-only">Filtrer les idées</span>
    <?php echo icon('list'); ?>
    <select data-tabs-select>
        <option value="all">Toutes les idées (<?php echo $counts['all']; ?>)</option>
        <?php if ($ctx['canGift']) : ?>
            <option value="available" class="gift-only-option">À offrir (<?php echo $counts['available']; ?>)</option>
            <option value="gifted" class="gift-only-option">Déjà offertes (<?php echo $counts['gifted']; ?>)</option>
        <?php endif; ?>
        <option value="favorite">Coups de cœur (<?php echo $counts['favorite']; ?>)</option>
        <?php if ($ctx['canEdit'] && received_enabled()) : ?>
            <option value="received">Reçus (<?php echo $counts['received']; ?>)</option>
        <?php endif; ?>
    </select>
    <?php echo icon('chevron-right', 'tabs-select__chevron'); ?>
</label>

<?php if (prices_enabled()) : ?>
    <div class="toolbar" data-price-tools<?php echo $hasPrices ? '' : ' hidden'; ?>>
        <div class="toolbar__group" role="group" aria-label="Budget">
            <span class="toolbar__label">Budget</span>
            <button type="button" aria-pressed="true" data-budget="">Tous</button>
            <button type="button" aria-pressed="false" data-budget="30">≤ 30 €</button>
            <button type="button" aria-pressed="false" data-budget="50">≤ 50 €</button>
            <button type="button" aria-pressed="false" data-budget="100">≤ 100 €</button>
        </div>
        <div class="toolbar__group" role="group" aria-label="Trier">
            <span class="toolbar__label">Trier</span>
            <button type="button" aria-pressed="true" data-sort="">Récentes</button>
            <button type="button" aria-pressed="false" data-sort="asc">Prix ↑</button>
            <button type="button" aria-pressed="false" data-sort="desc">Prix ↓</button>
        </div>
    </div>
<?php endif; ?>
