<?php
$id = (int) $object['id'];
$types = reaction_types();
$me = $ctx['me'];
$mine = 0;
$total = 0;

foreach ($object['reactions'] as $type => $reactions) {
    $total += count($reactions);
    foreach ($reactions as $reaction) {
        if ($me && (int) $reaction['user_id'] === (int) $me['id']) {
            $mine = (int) $type;
        }
    }
}

$names = array();
foreach ($object['reactions'] as $type => $reactions) {
    $names[] = reaction_names($reactions);
}
?>
<?php ob_start(); ?>
<?php if (0 < $total) : ?>
    <span class="reactions__summary" data-tip="<?php echo e(implode(', ', $names)); ?>" data-tip-position="bottom">
        <span class="reactions__icons">
            <?php foreach ($object['reactions'] as $type => $reactions) : ?>
                <img src="img/reaction/<?php echo (int) $type; ?>.png" alt="<?php echo e($types[$type]); ?>" width="24" height="24">
            <?php endforeach; ?>
        </span>
        <span class="reactions__total"><?php echo $total; ?></span>
    </span>
<?php else : ?>
    <span class="reactions__summary reactions__summary--empty"><?php echo icon('thumbs-up-o'); ?></span>
<?php endif; ?>
<?php $summary = ob_get_clean(); ?>

<?php if ($me) : ?>
    <div class="reactions__picker">
        <button type="button" class="reactions__toggle<?php echo $mine ? ' is-mine' : ''; ?>" aria-label="Réagir" aria-haspopup="true" data-reaction-toggle><?php echo $summary; ?></button>
        <form class="reactions__choices" method="post" action="actions/addReaction.php" data-ajax="reaction">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="object" value="<?php echo $id; ?>">
            <?php foreach ($types as $type => $label) : ?>
                <button type="submit" name="value" value="<?php echo (int) $type; ?>" data-tip="<?php echo e($label); ?>" aria-label="<?php echo e($label); ?>"<?php echo $mine === $type ? ' aria-pressed="true"' : ''; ?>>
                    <img src="img/reaction/<?php echo (int) $type; ?>.png" alt="" width="40" height="40">
                </button>
            <?php endforeach; ?>
        </form>
    </div>
<?php elseif (0 < $total) : ?>
    <?php echo $summary; ?>
<?php endif; ?>
