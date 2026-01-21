<?php
    $commentUser = (isset($commentData->user) && is_object($commentData->user)) ? $commentData->user : null;
    $commentUserId = ($commentUser && isset($commentUser->id)) ? $commentUser->id : null;
    $commentUserName = ($commentUser && isset($commentUser->nom)) ? $commentUser->nom : '';
    $commentAvatarStyle = '';
    
    if (null !== $commentUserId) {
        $commentAvatarStyle = retrieveAvatarUrl($commentUserId);
    }

    $commentContent = isset($commentData->content) ? $commentData->content : '';
    $commentId = isset($commentData->id) ? $commentData->id : null;
?>

<li class="comment" data-comment>
    <div class="comment-avatar" style="<?php echo $commentAvatarStyle; ?>"></div>

    <div class="comment-content">
        <span class="comment-user"><?php echo $commentUserName; ?></span> <?php echo nl2br($commentContent); ?>

        <?php if (isset($commentCanDelete) && $commentCanDelete && null !== $commentId) : ?>
            <a href="actions/deleteComment.php?id=<?php echo $commentId; ?>" class="delete-comment" data-delete-comment data-toggle="tooltip" data-original-title="supprimer mon commentaire">
                <i class="fa fa-trash"></i>
            </a>
        <?php endif; ?>
    </div>
</li>
