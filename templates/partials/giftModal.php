<?php
    if (!isset($gift) || !is_object($gift)) {
        return;
    }

    $giftId = isset($gift->id) ? $gift->id : null;
    if (null === $giftId) {
        return;
    }

    $giftName = isset($gift->name) ? $gift->name : '';
    $giftImageSrc = (isset($gift->image_src) && '' !== $gift->image_src) ? $gift->image_src : 'img/idea-default.jpg';

    $hasDescription = (isset($gift->has_description) && $gift->has_description);
    $fullDescription = isset($gift->full_description) ? $gift->full_description : '';

    $viewerIsAuthenticated = (isset($gift->viewer_is_authenticated) && $gift->viewer_is_authenticated);
    $viewerIsGuest = (isset($gift->viewer_is_guest) && $gift->viewer_is_guest);
    $sessionUserId = isset($gift->viewer_session_user_id) ? $gift->viewer_session_user_id : null;
    $sessionUserAvatarStyle = isset($gift->viewer_session_user_avatar_style) ? $gift->viewer_session_user_avatar_style : '';
    $currentUserCode = isset($gift->current_user_code) ? $gift->current_user_code : '';

    $giftIsGifted = (isset($gift->is_gifted) && $gift->is_gifted);
    $giftGiftedByCurrentUser = (isset($gift->is_gifted_by_current_user) && $gift->is_gifted_by_current_user);

    $giftLink = isset($gift->link) ? $gift->link : '';

    $commentsList = (isset($gift->comments) && is_array($gift->comments)) ? $gift->comments : array();
    $reactionsList = (isset($gift->reactions) && is_array($gift->reactions)) ? $gift->reactions : array();
?>

<div class="modal modal-object fade" data-object-id="<?php echo $giftId; ?>" id="object-<?php echo $giftId; ?>" tabindex="-1" role="dialog" aria-labelledby="modalObject<?php echo $giftId; ?>">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times"></i></span></button>
                <h3 class="modal-title" id="myModalLabel"><?php echo $giftName; ?></h3>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12 text-center" style="margin-bottom: 15px;">
                        <img src="<?php echo $giftImageSrc; ?>" style="width: 100%;">
                    </div>

                    <?php if ($hasDescription) : ?>
                        <div class="col-xs-12">
                            <p>
                                <?php echo nl2br($fullDescription); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="col-xs-12">
                        <?php foreach ($reactionsList as $reactionType => $reactionGroup) : ?>
                            <div class="reaction">
                                <div class="react-base" style="background-image: url(img/reaction/<?php echo $reactionType; ?>.png)"></div>

                                <?php foreach ($reactionGroup as $reactionItem) : ?>
                                    <?php
                                    $reactionUser = (isset($reactionItem->user) && is_object($reactionItem->user)) ? $reactionItem->user : null;
                                    $reactionUserName = ($reactionUser && isset($reactionUser->nom)) ? $reactionUser->nom : '';
                                    $reactionAvatarStyle = '';

                                    if ($reactionUser && isset($reactionUser->id)) {
                                        $reactionAvatarStyle = retrieveAvatarUrl($reactionUser->id);
                                    }
                                    ?>
                                    <div class="react-avatar" data-toggle="tooltip" data-original-title="<?php echo $reactionUserName; ?>" style="<?php echo $reactionAvatarStyle; ?>"></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-xs-12">
                        <div class="comments" data-comments>
                            <h5>commentaires:</h5>
                            <ul class="comments-list" data-comments-list>
                                <?php foreach ($commentsList as $commentView) : ?>
                                    <?php
                                    if (!is_object($commentView)) {
                                        continue;
                                    }

                                    $commentCanDelete = false;
                                    $commentUser = (isset($commentView->user) && is_object($commentView->user)) ? $commentView->user : null;
                                    if ($viewerIsAuthenticated && $commentUser && isset($commentUser->id) && $commentUser->id == $sessionUserId && isset($commentView->id)) {
                                        $commentCanDelete = true;
                                    }

                                    render_template('partials/commentItem.php', array(
                                        'commentData' => $commentView,
                                        'commentCanDelete' => $commentCanDelete,
                                    ));
                                    ?>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($viewerIsAuthenticated) : ?>
                                <form data-form-comment action="actions/addComment.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" data-object-id name="productId" value="<?php echo $giftId; ?>">

                                    <div class="form-comment-content">
                                        <div class="comment-avatar" style="<?php echo $sessionUserAvatarStyle; ?>"></div>
                                        <input data-submit-comment type="submit" name="submit" value="ok" class="btn btn-primary">
                                        <textarea rows="1" class="comment-form-content" name="content" required="required"></textarea>
                                    </div>
                                </form>
                            <?php else : ?>
                                <div class="help-block text-center">
                                    <p>connectez vous pour commenter cette idée KDO !</p>

                                    <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#connectModal">
                                        <i class="fa fa-power-off"></i> Se connecter
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <?php if ($viewerIsGuest) : ?>
                    <div class="modal-footer__gift">
                        <?php if (!$giftIsGifted) : ?>
                            <a href="actions/objectGifted.php?id=<?php echo $giftId; ?>&friendId=<?php echo $currentUserCode; ?>" class="icon-bottom primary gifted-display gift-btn" data-toggle="tooltip" data-original-title="Offrir ce Kdo">
                                <i class="fa fa-gift"></i>
                            </a>
                        <?php elseif ($giftGiftedByCurrentUser) : ?>
                            <a href="actions/objectNotGifted.php?id=<?php echo $giftId; ?>&friendId=<?php echo $currentUserCode; ?>" class="icon-bottom green gifted-display gift-btn" data-toggle="tooltip" data-original-title="Ne plus offrir ce Kdo">
                                <i class="fa fa-check"></i>
                                <span class="sub-icon">
                                    <i class="fa fa-times"></i>
                                </span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ('' !== $giftLink) : ?>
                    <div class="modal-footer__link text-center">
                        <a target="_blank" href="<?php echo $giftLink; ?>" class="btn btn-primary btn-buy" data-toggle2="tooltip" data-original-title="Voir ce KDO">
                            Voir ce KDO
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
