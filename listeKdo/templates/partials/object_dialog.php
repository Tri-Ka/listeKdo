<?php
$id = (int) $object['id'];
$me = $ctx['me'];
?>
<dialog class="modal modal--object" id="object-<?php echo $id; ?>" aria-labelledby="object-<?php echo $id; ?>-title">
    <header class="modal__header">
        <h2 id="object-<?php echo $id; ?>-title"><?php echo e($object['nom']); ?></h2>
        <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
    </header>

    <div class="modal__body">
        <div class="detail__media">
            <img class="detail__image" src="<?php echo e('' !== $object['image'] ? $object['image'] : 'img/idea-default.svg'); ?>" alt="" loading="lazy" decoding="async" data-fallback="img/idea-default.svg">
        </div>

        <?php if (null !== $object['price']) : ?>
            <p class="detail__price"><?php echo e(format_price($object['price'])); ?> <small>prix indicatif</small></p>
        <?php endif; ?>

        <?php if ('' !== $object['description']) : ?>
            <p class="detail__text"><?php echo multiline($object['description']); ?></p>
        <?php endif; ?>

        <?php if ($ctx['canGift'] && participations_enabled() && !$object['is_collection'] && !$object['received'] && null === $object['gifted_by']) : ?>
            <div class="gift-only-block">
                <?php echo render('partials/group', array('object' => $object, 'ctx' => $ctx)); ?>
            </div>
        <?php endif; ?>

        <?php if ($object['is_collection']) : ?>
            <section class="detail__items">
                <h3>Les éléments de la collection</h3>
                <?php echo render('partials/items', array('object' => $object, 'ctx' => $ctx, 'limit' => 0)); ?>
            </section>
        <?php endif; ?>

        <div class="reactors" data-reactors-slot="<?php echo $id; ?>">
            <?php echo render('partials/reactors', array('object' => $object)); ?>
        </div>

        <section class="comments">
            <h3>Commentaires</h3>
            <ul class="comments__list" data-comments="<?php echo $id; ?>">
                <?php foreach ($object['comments'] as $comment) : ?>
                    <?php echo render('partials/comment', array('comment' => $comment, 'ctx' => $ctx)); ?>
                <?php endforeach; ?>
            </ul>

            <?php if ($me) : ?>
                <form class="comment-form" method="post" action="actions/addComment.php" data-ajax="comment">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="productId" value="<?php echo $id; ?>">
                    <?php echo avatar($me, 'comment__avatar'); ?>
                    <textarea name="content" rows="1" required maxlength="2000" placeholder="Écrire un commentaire…" aria-label="Votre commentaire" data-autosize></textarea>
                    <button type="submit" class="icon-btn icon-btn--primary" aria-label="Envoyer"><?php echo icon('paper-plane'); ?></button>
                </form>
            <?php else : ?>
                <div class="hint">
                    <p>Connectez-vous pour commenter cette idée !</p>
                    <button type="button" class="btn btn--primary" data-open="login-dialog"><?php echo icon('power-off'); ?> Se connecter</button>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($ctx['canGift'] || '' !== $object['link']) : ?>
        <footer class="modal__footer">
            <?php if ($ctx['canGift'] && !$object['received']) : ?>
                <div class="gift-slot" data-gift-slot="<?php echo $id; ?>">
                    <?php echo render('partials/gift', array('object' => $object, 'ctx' => $ctx)); ?>
                </div>
            <?php endif; ?>

            <?php if ('' !== $object['link']) : ?>
                <a class="btn btn--light" href="<?php echo e($object['link']); ?>" target="_blank" rel="noopener nofollow"><?php echo icon('cart-shopping'); ?> Voir le produit <?php echo icon('arrow-up-right-from-square'); ?></a>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</dialog>
