<?php
/*
 * Éléments d'une collection.
 * $limit : nombre d'éléments affichés (0 = tous) ; au-delà, un lien ouvre la fiche.
 * Seuls les autres utilisateurs connectés voient les réservations ($ctx['canGift']), jamais le propriétaire.
 */
$id = (int) $object['id'];
$me = $ctx['me'];
$canGift = $ctx['canGift'];
$canEdit = !empty($ctx['canEdit']);
$visibleItems = array();
foreach ($object['items'] as $item) {
    if ($canEdit || empty($item['received_at'])) {
        $visibleItems[] = $item;
    }
}
$items = $limit ? array_slice($visibleItems, 0, $limit) : $visibleItems;
$hidden = count($visibleItems) - count($items);
?>
<ul class="items" data-items-slot="<?php echo $id; ?>" data-items-mode="<?php echo $limit ? 'card' : 'detail'; ?>">
    <?php foreach ($items as $item) : ?>
        <?php
        $taken = null !== $item['gifted_by'];
        $mine = $taken && $me && (int) $item['gifted_by'] === (int) $me['id'];
        $received = !empty($item['received_at']);
        $link = isset($item['link']) ? safe_url($item['link']) : '';
        $state = $canGift ? ($mine ? ' item--mine' : ($taken ? ' item--taken' : '')) : '';
        ?>
        <li class="item<?php echo $state; ?><?php echo $received ? ' item--received' : ''; ?>">
            <?php if ($canGift && !$received && !$object['received']) : ?>
                <form method="post" action="actions/itemGifted.php" data-ajax="item" class="item__form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                    <input type="hidden" name="gift" value="<?php echo $mine ? '0' : '1'; ?>">
                    <button type="submit" class="item__check" role="checkbox" aria-checked="<?php echo $mine ? 'true' : ($taken ? 'mixed' : 'false'); ?>"
                        <?php echo $taken && !$mine ? 'disabled' : ''; ?>
                        aria-label="<?php echo e(($mine ? 'Ne plus offrir : ' : ($taken ? 'Déjà réservé : ' : "Je l'offre : ")) . $item['nom']); ?>">
                        <?php echo icon('check'); ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if ('' !== $link) : ?>
                <a class="item__name item__link" href="<?php echo e($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($item['nom']); ?> <?php echo icon('arrow-up-right-from-square'); ?></a>
            <?php else : ?>
                <span class="item__name"><?php echo e($item['nom']); ?></span>
            <?php endif; ?>
            <?php if ($canEdit && items_received_enabled()) : ?>
                <form method="post" action="actions/itemReceived.php" data-ajax="received" class="item__received-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                    <button type="submit" class="round-btn round-btn--sm item__received" aria-pressed="<?php echo $received ? 'true' : 'false'; ?>" aria-label="<?php echo e(($received ? 'Remettre dans la liste : ' : 'Je l\'ai reçu : ') . $item['nom']); ?>" data-tip="<?php echo $received ? 'Reçu · remettre dans la liste' : 'Je l\'ai reçu'; ?>">
                        <?php echo icon($received ? 'check' : 'box-archive'); ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($canGift && $taken && !$received && !$object['received']) : ?>
                <span class="item__by">
                    <?php echo avatar($mine ? $me : $item['gifted_by_user'], 'item__avatar'); ?>
                    <?php echo $mine ? 'Vous' : e($item['gifted_by_user'] ? $item['gifted_by_user']['nom'] : 'Réservé'); ?>
                </span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    <?php if (0 < $hidden) : ?>
        <li class="items__more">
            <button type="button" class="link-btn" data-open="object-<?php echo $id; ?>">+ <?php echo (int) $hidden; ?> autre<?php echo 1 < $hidden ? 's' : ''; ?></button>
        </li>
    <?php endif; ?>
</ul>
