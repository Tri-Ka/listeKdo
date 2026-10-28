<?php
$me = $ctx['me'];
$image = comment_image_url($comment);
$date = isset($comment['created_at']) ? comment_date($comment['created_at']) : '';
$canReact = $me && comment_reactions_enabled();
?>
<li class="comment">
    <?php echo avatar($comment['user'], 'comment__avatar'); ?>
    <div class="comment__main">
        <div class="comment__row">
            <div class="comment__bubble<?php echo '' !== $image ? ' comment__bubble--image' : ''; ?>">
                <strong class="comment__author"><?php echo e($comment['user']['nom']); ?></strong>
                <?php if ('' !== $image) : ?>
                    <a class="comment__image" href="<?php echo e($image); ?>" target="_blank" rel="noopener" aria-label="Agrandir l'image" data-image-viewer>
                        <img src="<?php echo e($image); ?>" alt="Image envoyée par <?php echo e($comment['user']['nom']); ?>" loading="lazy" decoding="async">
                    </a>
                <?php endif; ?>
                <?php if ('' !== (string) $comment['content']) : ?>
                    <span class="comment__text"><?php echo multiline($comment['content']); ?></span>
                <?php endif; ?>
                <?php if ('' !== $date) : ?>
                    <time class="comment__date" datetime="<?php echo e(date('c', strtotime($comment['created_at']))); ?>" title="<?php echo e(date('d/m/Y à H:i', strtotime($comment['created_at']))); ?>"><?php echo e($date); ?></time>
                <?php endif; ?>

                <?php if ($me && (int) $comment['user_id'] === (int) $me['id']) : ?>
                    <form method="post" action="actions/deleteComment.php" data-ajax="delete-comment" class="comment__delete">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                        <button type="submit" title="Supprimer mon commentaire" aria-label="Supprimer mon commentaire" data-confirm="Il sera définitivement effacé." data-confirm-title="Supprimer ce commentaire ?" data-confirm-ok="Supprimer" data-confirm-icon="trash-can"><?php echo icon('trash-can'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($canReact) : ?>
                <button type="button" class="comment__react" data-comment-react="<?php echo (int) $comment['id']; ?>" aria-label="Réagir à ce commentaire" title="Réagir" aria-expanded="false"><?php echo icon('face-smile'); ?></button>
            <?php endif; ?>
        </div>
        <?php if (comment_reactions_enabled()) : ?>
            <?php echo render('partials/comment_reactions', array('comment' => $comment, 'me' => $me)); ?>
        <?php endif; ?>
    </div>
</li>
