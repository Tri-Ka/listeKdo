<?php $types = reaction_types(); ?>
<?php foreach ($object['reactions'] as $type => $reactions) : ?>
    <div class="reactors__row">
        <img class="reactors__type" src="img/reaction/<?php echo (int) $type; ?>.png" alt="<?php echo e($types[$type]); ?>" width="36" height="36">
        <?php foreach ($reactions as $reaction) : ?>
            <span data-tip="<?php echo e($reaction['user']['nom']); ?>"><?php echo avatar($reaction['user'], 'reactors__avatar'); ?></span>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
