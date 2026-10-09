<?php
$categories = array();
foreach ($gemTable['rows'] as $row) {
    $categories[$row['category']] = $row['category'];
}
$hasRarity = 'shop' === $key;
$rarities = skin_rarities();
$rarityKeys = array_keys($rarities);
$columns = array('name' => 'Nom', 'category' => 'Catégorie');
if ($hasRarity) $columns['rarity'] = 'Rareté';
$columns['default'] = 'Par défaut';
$columns['amount'] = 'Gemmes';
?>
<section class="panel gem-table" id="gem-<?php echo e($key); ?>" data-gem-table="<?php echo e($key); ?>" aria-labelledby="gem-title-<?php echo e($key); ?>">
    <?php echo render('partials/admin_table_head', array('prefix' => 'gem', 'caption' => 'Rechercher dans : ' . $gemTable['title'], 'captionId' => 'gem-title-' . $key, 'placeholder' => 'Rechercher un nom…')); ?>
    <div class="dt__bar" data-gem-controls hidden>
        <div class="dt__filters">
        <select class="dt__select" data-gem-category aria-label="Filtrer par catégorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $category) : ?>
                <option value="<?php echo e($category); ?>"><?php echo e($category); ?></option>
            <?php endforeach; ?>
        </select>
        </div>
        <p class="dt__count" data-gem-count aria-live="polite"></p>
    </div>
    <div class="dt__scroll">
        <table class="dt gem-table__grid">
            <thead><tr>
                <?php foreach ($columns as $column => $label) : ?>
                    <?php echo render('partials/admin_local_th', array('prefix' => 'gem', 'column' => $column, 'label' => $label, 'numeric' => in_array($column, array('default', 'amount')))); ?>
                <?php endforeach; ?>
                <th scope="col" class="dt__actions"><span class="sr-only">Actions</span></th>
            </tr></thead>
            <tbody>
                <?php foreach ($gemTable['rows'] as $index => $row) : ?>
                    <?php $amount = 'badge_gems' === $row['name'] && 0 === (int) $row['value'] ? $row['default'] : $row['value']; ?>
                    <tr data-gem-row data-gem-definition="<?php echo e(kdo_json($row)); ?>" data-name="<?php echo e($row['label']); ?>" data-category="<?php echo e($row['category']); ?>"<?php if ($hasRarity) : ?> data-rarity="<?php echo (int) array_search($row['rarity'], $rarityKeys); ?>"<?php endif; ?> data-default="<?php echo (int) $row['default']; ?>" data-amount="<?php echo (int) $amount; ?>">
                        <td data-label="Nom">
                            <div class="gem-table__item">
                                <?php if (isset($row['preview'])) : ?>
                                    <?php echo render('partials/admin_shop_preview', array('preview' => $row['preview'], 'me' => $me)); ?>
                                <?php endif; ?>
                                <span class="gem-table__name"><strong><?php echo e($row['label']); ?></strong><?php if ('' !== $row['note']) : ?><small class="dt__sub"><?php echo e($row['note']); ?></small><?php endif; ?></span>
                            </div>
                        </td>
                        <td data-label="Catégorie"><span class="gem-table__category"><?php echo e($row['category']); ?></span></td>
                        <?php if ($hasRarity) : ?>
                            <td data-label="Rareté"><?php echo e($rarities[$row['rarity']]); ?></td>
                        <?php endif; ?>
                        <td data-label="Par défaut" class="dt__num"><?php echo (int) $row['default']; ?></td>
                        <td data-label="Gemmes" class="dt__num">
                            <strong data-gem-amount><?php echo (int) $amount; ?></strong>
                            <small class="dt__sub" data-gem-auto><?php echo 'badge_gems' === $row['name'] && 0 === (int) $row['value'] ? 'Automatique' : ''; ?></small>
                        </td>
                        <td data-label="Actions" class="dt__actions">
                            <button type="button" class="btn btn--light btn--sm" data-gem-edit aria-haspopup="dialog" aria-controls="gem-edit-dialog"<?php echo !$row['editable'] ? ' disabled' : ''; ?>>Modifier</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr data-gem-empty hidden><td colspan="<?php echo count($columns) + 1; ?>" class="dt__empty">Aucun résultat pour cette recherche.</td></tr>
            </tbody>
        </table>
    </div>
    <?php echo render('partials/admin_local_footer', array('prefix' => 'gem', 'paginationLabel' => 'Pagination de ' . $gemTable['title'])); ?>
    <p class="admin__hint dt__note"><?php echo e($gemTable['hint']); ?></p>
</section>
