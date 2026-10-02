<dialog class="modal" id="my-gifts-dialog" aria-labelledby="my-gifts-title">
    <header class="modal__header">
        <h2 id="my-gifts-title">Les cadeaux que j'offre</h2>
        <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
    </header>
    <div class="modal__body">
        <?php if (0 === count($myGifts)) : ?>
            <p class="my-gifts__empty">Vous n'avez encore rien prévu d'offrir. Sur la liste d'un ami, cliquez sur « Je l'offre ».</p>
        <?php else : ?>
            <?php foreach ($myGifts as $group) : ?>
                <?php $owner = $group['owner']; ?>
                <section class="my-gifts__group">
                    <h3>
                        <?php echo avatar($owner, 'my-gifts__avatar'); ?>
                        <a href="index.php?user=<?php echo e(rawurlencode($owner['code'])); ?>"><?php echo e($owner['nom']); ?></a>
                        <?php if ('' !== event_short($owner)) : ?>
                            <small><?php echo icon('calendar-days'); ?> <?php echo e(event_short($owner)); ?></small>
                        <?php endif; ?>
                    </h3>
                    <ul>
                        <?php foreach ($group['gifts'] as $gift) : ?>
                            <?php $object = $gift['object']; ?>
                            <li>
                                <a class="my-gifts__item" href="index.php?user=<?php echo e(rawurlencode($owner['code'])); ?>#idea-<?php echo (int) $object['id']; ?>">
                                    <img src="<?php echo e('' !== $object['image'] ? $object['image'] : 'img/idea-default.svg'); ?>" alt="" loading="lazy" data-fallback="img/idea-default.svg">
                                    <span class="my-gifts__name">
                                        <?php echo e($object['nom']); ?>
                                        <?php if ('item' === $gift['kind']) : ?>
                                            <small><?php echo icon('check'); ?> <?php echo e($gift['detail']); ?></small>
                                        <?php elseif ('group' === $gift['kind']) : ?>
                                            <small><?php echo icon('users'); ?> À plusieurs<?php echo null !== $gift['detail'] ? ' · ma part : ' . e(format_price($gift['detail'])) : ''; ?></small>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (null !== $object['price'] && 'alone' === $gift['kind']) : ?>
                                        <span class="my-gifts__price"><?php echo e(format_price($object['price'])); ?></span>
                                    <?php endif; ?>
                                    <?php if ('' !== $object['link']) : ?>
                                        <?php echo icon('chevron-right', 'my-gifts__chevron'); ?>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</dialog>
