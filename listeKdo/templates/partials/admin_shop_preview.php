<?php /* Miniature d’un article, avec les mêmes visuels et effets que la boutique. */ ?>
<div class="gem-table__preview gem-table__preview--<?php echo e($preview['kind']); ?>" aria-hidden="true"<?php if ('skin' === $preview['kind']) : ?> style="--preview-from: <?php echo e($preview['from']); ?>; --preview-to: <?php echo e($preview['to']); ?>"<?php endif; ?>>
    <?php if ('skin' === $preview['kind']) : ?>
        <img class="gem-table__preview-title" src="<?php echo e(asset($preview['title'])); ?>" width="88" height="34" alt="" loading="lazy" decoding="async">
    <?php else : ?>
        <?php echo render('partials/shop_accessory', array('kind' => $preview['kind'], 'key' => $preview['key'], 'me' => $me)); ?>
    <?php endif; ?>
</div>
