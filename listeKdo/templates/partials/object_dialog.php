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

        <?php if (!empty($object['suggestion'])) : ?>
            <p class="detail__suggestion">
                <?php if (!empty($object['suggested_by_user'])) : ?>
                    <span class="suggester"><?php echo avatar($object['suggested_by_user'], 'suggester__avatar'); ?><?php echo icon('lightbulb', 'suggester__bulb'); ?></span>
                <?php else : ?>
                    <?php echo icon('lightbulb'); ?>
                <?php endif; ?>
                <span>Suggérée par <strong><?php echo !empty($object['suggested_by_user']) ? e($object['suggested_by_user']['nom']) : 'un ami'; ?></strong>.
                <?php echo e($ctx['owner']['nom']); ?> ne voit pas cette idée : la surprise reste entière.</span></p>
        <?php endif; ?>

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

        <div class="detail__react">
            <?php if ($me) : ?>
                <div class="reactions reactions--compact" data-reactions-slot="<?php echo $id; ?>" data-reactions-compact>
                    <?php echo render('partials/reactions', array('object' => $object, 'ctx' => $ctx, 'compact' => true)); ?>
                </div>
            <?php endif; ?>
            <div class="reactors" data-reactors-slot="<?php echo $id; ?>">
                <?php echo render('partials/reactors', array('object' => $object)); ?>
            </div>
        </div>

        <section class="comments">
            <h3>Commentaires</h3>
            <ul class="comments__list" data-comments="<?php echo $id; ?>">
                <?php foreach ($object['comments'] as $comment) : ?>
                    <?php echo render('partials/comment', array('comment' => $comment, 'ctx' => $ctx)); ?>
                <?php endforeach; ?>
            </ul>

            <?php if ($me) : ?>
                <?php $commentImages = comment_images_enabled(); ?>
                <?php $giphyKey = $commentImages ? giphy_key() : ''; ?>
                <form class="comment-form<?php echo '' !== $giphyKey ? ' comment-form--gif' : ''; ?>" method="post" action="actions/addComment.php" data-ajax="comment"<?php echo $commentImages ? ' enctype="multipart/form-data" data-comment-images' : ''; ?><?php echo '' !== $giphyKey ? ' data-giphy-key="' . e($giphyKey) . '"' : ''; ?>>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="productId" value="<?php echo $id; ?>">
                    <?php echo avatar($me, 'comment__avatar'); ?>
                    <div class="comment-form__field">
                        <?php if ($commentImages) : ?>
                            <div class="comment-form__preview" data-comment-preview hidden>
                                <img src="" alt="Image à envoyer">
                                <button type="button" data-comment-image-remove aria-label="Retirer l'image" title="Retirer l'image"><?php echo icon('xmark'); ?></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($commentImages) : ?>
                            <?php // Champ éditable plutôt qu'une zone de texte : les claviers de téléphone (Gboard…) peuvent y insérer GIF et autocollants. ?>
                            <div class="comment-form__input" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Votre commentaire" data-placeholder="Écrire un commentaire…" enterkeyhint="send" data-comment-editor></div>
                            <textarea name="content" maxlength="2000" hidden></textarea>
                        <?php else : ?>
                            <textarea name="content" rows="1" required maxlength="2000" placeholder="Écrire un commentaire…" aria-label="Votre commentaire" data-autosize></textarea>
                        <?php endif; ?>
                        <?php if ('' !== $giphyKey) : ?>
                            <button type="button" class="comment-form__gif" data-gif-open title="Chercher un GIF" aria-label="Chercher un GIF" aria-expanded="false">GIF</button>
                        <?php endif; ?>
                        <?php if ($commentImages) : ?>
                            <label class="comment-form__attach" title="Ajouter une photo ou un GIF">
                                <?php echo icon('image'); ?>
                                <span class="sr-only">Ajouter une photo ou un GIF</span>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/gif" data-max-size="1200" data-comment-image>
                            </label>
                        <?php endif; ?>
                    </div>
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
