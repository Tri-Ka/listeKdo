<?php $me = $ctx['me']; ?>
<li class="comment">
    <?php echo avatar($comment['user'], 'comment__avatar'); ?>
    <div class="comment__bubble">
        <strong class="comment__author"><?php echo e($comment['user']['nom']); ?></strong>
        <span class="comment__text"><?php echo multiline($comment['content']); ?></span>

        <?php if ($me && (int) $comment['user_id'] === (int) $me['id']) : ?>
            <form method="post" action="actions/deleteComment.php" data-ajax="delete-comment" class="comment__delete">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                <button type="submit" title="Supprimer mon commentaire" aria-label="Supprimer mon commentaire" data-confirm="Supprimer ce commentaire ?"><?php echo icon('trash-can'); ?></button>
            </form>
        <?php endif; ?>
    </div>
</li>
