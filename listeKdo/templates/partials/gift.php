<?php
$id = (int) $object['id'];
$me = $ctx['me'];
$giver = $object['gifted_by_user'];
$groups = participations_enabled() && !$object['is_collection'];
$joined = false;
foreach ($object['participants'] as $participant) {
    if ((int) $participant['user_id'] === (int) $me['id']) {
        $joined = true;
    }
}
?>
<?php if ($object['is_collection']) : ?>
    <?php if ($object['complete']) : ?>
        <span class="gifted-pill"><?php echo icon('circle-check'); ?> Tout est réservé</span>
    <?php else : ?>
        <span class="collection-pill"><?php echo icon('gift'); ?> <?php echo (int) $object['items_gifted']; ?> / <?php echo (int) $object['items_total']; ?> réservés</span>
    <?php endif; ?>
<?php elseif ($object['is_group']) : ?>
    <?php
    // Version courte (le pied de la vignette est étroit) ; le détail est dans l'info-bulle et la fiche.
    $count = count($object['participants']);
    $people = $count . ' participant' . (1 < $count ? 's' : '');
    $progress = null !== $object['price'] ? min(100, round($object['group_total'] * 100 / $object['price'])) : null;
    $tip = null !== $object['price'] ? $people . ' · ' . format_price($object['group_total']) . ' sur ' . format_price($object['price']) : $people;
    ?>
    <button type="button" class="collection-pill group-pill<?php echo $joined ? ' collection-pill--mine' : ''; ?>" data-open="object-<?php echo $id; ?>" data-focus="amount"
        data-tip="<?php echo e($tip); ?>" aria-label="<?php echo e($tip); ?>"<?php echo null !== $progress ? ' style="--progress: ' . (int) $progress . '%"' : ''; ?>>
        <?php echo icon('users'); ?>
        <?php if (null !== $progress) : ?>
            <?php echo $count; ?> · <?php echo e(str_replace(' €', '', format_price($object['group_total']))); ?> / <?php echo e(format_price($object['price'])); ?>
        <?php else : ?>
            <?php echo e($people); ?>
        <?php endif; ?>
    </button>
<?php elseif (null === $object['gifted_by']) : ?>
    <?php if ($groups) : ?>
        <?php // Choix « seul » ou « à plusieurs » dans une petite fenêtre (#gift-choice-dialog). ?>
        <button type="button" class="btn btn--primary gift-btn" data-gift-choice="<?php echo $id; ?>" data-gift-name="<?php echo e($object['nom']); ?>"><?php echo icon('gift'); ?> Je l'offre <?php echo icon('chevron-right', 'gift-btn__chevron'); ?></button>
    <?php else : ?>
        <form method="post" action="actions/objectGifted.php" data-ajax="gift">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button type="submit" class="btn btn--primary gift-btn"><?php echo icon('gift'); ?> Je l'offre <?php echo icon('chevron-right', 'gift-btn__chevron'); ?></button>
        </form>
    <?php endif; ?>
<?php elseif ((int) $object['gifted_by'] === (int) $me['id']) : ?>
    <form method="post" action="actions/objectNotGifted.php" data-ajax="gift">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <button type="submit" class="gifted-pill gifted-pill--mine" title="Cliquer pour ne plus l'offrir" data-confirm="Le cadeau redeviendra disponible pour les autres." data-confirm-title="Vous ne l'offrez plus ?" data-confirm-ok="Je ne l'offre plus" data-confirm-icon="gift"><?php echo avatar($me, 'gifted-pill__avatar', true, ''); ?> Vous l'offrez</button>
    </form>
<?php else : ?>
    <span class="gifted-pill" title="Offert par <?php echo e($giver['nom']); ?>"><?php echo avatar($giver, 'gifted-pill__avatar', true, ''); ?> Offert par <?php echo e($giver['nom']); ?></span>
<?php endif; ?>
