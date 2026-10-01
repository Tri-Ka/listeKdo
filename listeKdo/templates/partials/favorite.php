<?php $id = (int) $object['id']; ?>
<?php if ($ctx['canEdit']) : ?>
    <form method="post" action="actions/toggleFavorite.php" data-ajax="favorite" class="card__favorite">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <button type="submit" class="heart-btn" aria-pressed="<?php echo $object['favorite'] ? 'true' : 'false'; ?>" title="Coup de cœur" aria-label="Coup de cœur">
            <?php echo icon('heart', 'heart-btn__on'); ?><?php echo icon('heart-o', 'heart-btn__off'); ?>
        </button>
    </form>
<?php elseif ($object['favorite']) : ?>
    <span class="card__favorite heart-btn" aria-pressed="true" data-tip="Coup de cœur de <?php echo e($ctx['owner']['nom']); ?>"><?php echo icon('heart', 'heart-btn__on'); ?></span>
<?php endif; ?>
