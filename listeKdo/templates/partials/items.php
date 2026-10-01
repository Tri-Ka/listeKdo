<?php
/*
 * Éléments d'une collection.
 * $limit : nombre d'éléments affichés (0 = tous) ; au-delà, un lien ouvre la fiche.
 * Seuls les autres utilisateurs connectés voient les réservations ($ctx['canGift']), jamais le propriétaire.
 */
$id = (int) $object['id'];
$me = $ctx['me'];
$canGift = $ctx['canGift'];
$items = $limit ? array_slice($object['items'], 0, $limit) : $object['items'];
$hidden = count($object['items']) - count($items);
?>
<ul class="items" data-items-slot="<?php echo $id; ?>" data-items-mode="<?php echo $limit ? 'card' : 'detail'; ?>">
    <?php foreach ($items as $item) : ?>
        <?php
        $taken = null !== $item['gifted_by'];
        $mine = $taken && $me && (int) $item['gifted_by'] === (int) $me['id'];
        $state = $canGift ? ($mine ? ' item--mine' : ($taken ? ' item--taken' : '')) : '';
        ?>
        <li class="item<?php echo $state; ?>">
            <?php if ($canGift) : ?>
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
            <span class="item__name"><?php echo e($item['nom']); ?></span>
            <?php if ($canGift && $taken) : ?>
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
