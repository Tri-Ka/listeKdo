<?php
    if (!isset($gift) || !is_object($gift)) {
        return;
    }

    $reactionLabels = (isset($reactionLabels) && is_array($reactionLabels)) ? $reactionLabels : array();

    $giftId = isset($gift->id) ? $gift->id : null;
    if (null === $giftId) {
        return;
    }

    $giftName = isset($gift->name) ? $gift->name : '';
    $hasCustomImage = (isset($gift->has_custom_image) && $gift->has_custom_image);
    $giftImageSrc = (isset($gift->image_src) && '' !== $gift->image_src) ? $gift->image_src : 'img/idea-default.jpg';

    $hasDescription = (isset($gift->has_description) && $gift->has_description);
    $shortDescription = isset($gift->short_description) ? $gift->short_description : '';
    $isDescriptionTruncated = (isset($gift->is_description_truncated) && $gift->is_description_truncated);

    $giftLink = isset($gift->link) ? $gift->link : '';

    $comments = (isset($gift->comments) && is_array($gift->comments)) ? $gift->comments : array();
    $commentsCount = isset($gift->comments_count) ? $gift->comments_count : count($comments);

    $reactionGroups = (isset($gift->reaction_groups) && is_array($gift->reaction_groups)) ? $gift->reaction_groups : array();
    $hasReactions = isset($gift->has_reactions) ? $gift->has_reactions : (count($reactionGroups) > 0);

    $viewerIsAuthenticated = isset($gift->viewer_is_authenticated) ? $gift->viewer_is_authenticated : false;
    $viewerIsOwner = isset($gift->viewer_can_edit) ? $gift->viewer_can_edit : false;
    $viewerIsGuest = isset($gift->viewer_is_guest) ? $gift->viewer_is_guest : false;
    $currentUserCode = isset($gift->current_user_code) ? $gift->current_user_code : '';

    $giftIsGifted = isset($gift->is_gifted) ? $gift->is_gifted : false;
    $giftGiftedByCurrentUser = isset($gift->is_gifted_by_current_user) ? $gift->is_gifted_by_current_user : false;
    $giftedByCode = isset($gift->gifted_by_code) ? $gift->gifted_by_code : '';
    $giftedByName = isset($gift->gifted_by_name) ? $gift->gifted_by_name : '';
    $giftedByStyle = isset($gift->gifted_by_style) ? $gift->gifted_by_style : '';
?>

<div class="col-xs-12 col-sm-6 col-md-4 grid-item">
    <div id="idea-<?php echo $giftId; ?>" class="panel panel-default panel-object<?php if ($viewerIsGuest && $giftIsGifted) : ?> gifted<?php endif; ?>">
        <?php if ($viewerIsOwner) : ?>
            <a href="#" class="delete-obj edit-obj" data-toggle="modal" data-target="#modal-edit-object-<?php echo $giftId; ?>" data-toggle2="tooltip" data-original-title="Modifier">
                <i class="fa fa-pencil"></i>
            </a>
        <?php endif; ?>

        <div class="nom">
            <?php echo $giftName; ?>
        </div>

        <a href="#" data-toggle="modal" data-target="#object-<?php echo $giftId; ?>" class="img-container text-center">
            <img src="<?php echo $giftImageSrc; ?>"<?php if (!$hasCustomImage) : ?> style="width: 100%;"<?php endif; ?>>
        </a>

        <?php if ($hasDescription) : ?>
            <div class="panel-detail">
                <p>
                    <?php echo nl2br($shortDescription); ?>
                    <?php if ($isDescriptionTruncated) : ?>
                        [...]
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="panel-bottom carded">
            <a href="#" data-toggle="modal" data-target="#object-<?php echo $giftId; ?>" class="icon-bottom" data-toggle2="tooltip" data-original-title="Commentaires">
                <i class="fa fa-comments-o"></i>
                <?php if ($commentsCount > 0) : ?>
                    <span class="sub-icon comments-count" data-comments-count-<?php echo $giftId; ?>><?php echo $commentsCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="#" data-show-modal class="icon-bottom" data-toggle="modal" data-target="#object-<?php echo $giftId; ?>" data-toggle2="tooltip" data-original-title="Plus d'infos">
                <i class="fa fa-info-circle"></i>
            </a>

            <?php if ('' !== $giftLink) : ?>
                <a target="_blank" href="<?php echo $giftLink; ?>" class="icon-bottom" data-toggle2="tooltip" data-original-title="Accéder au site">
                    <i class="fa fa-shopping-cart"></i>
                </a>
            <?php endif; ?>

            <?php if ($viewerIsGuest) : ?>
                <?php if (!$giftIsGifted) : ?>
                    <a href="actions/objectGifted.php?id=<?php echo $giftId; ?>&friendId=<?php echo $currentUserCode; ?>" class="icon-bottom primary gifted-display gift-btn" data-toggle="tooltip" data-original-title="Offrir ce Kdo">
                        <i class="fa fa-gift"></i>
                    </a>
                <?php elseif ($giftGiftedByCurrentUser) : ?>
                    <a href="actions/objectNotGifted.php?id=<?php echo $giftId; ?>&friendId=<?php echo $currentUserCode; ?>" class="icon-bottom gifted-display green gift-btn" data-toggle="tooltip" data-original-title="Ne plus offrir ce Kdo">
                        <i class="fa fa-check"></i>
                        <span class="sub-icon">
                            <i class="fa fa-times"></i>
                        </span>
                    </a>
                <?php elseif ('' !== $giftedByCode) : ?>
                    <a href="index.php?user=<?php echo $giftedByCode; ?>" data-toggle="tooltip" data-original-title="Offert par: <?php echo $giftedByName; ?>" class="gifted-by-infos__avatar icon-bottom gifted-display" style="<?php echo $giftedByStyle; ?>">
                        <i class="fa"></i>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <div class="reaction-list-container">
                <a href="#" data-reaction-list class="reaction-list">
                    <?php foreach ($reactionGroups as $reactionGroupData) : ?>
                        <?php
                        if (is_array($reactionGroupData)) {
                            $reactionGroupObject = new stdClass();
                            foreach ($reactionGroupData as $reactionGroupKey => $reactionGroupValue) {
                                $reactionGroupObject->$reactionGroupKey = $reactionGroupValue;
                            }
                            $reactionGroupData = $reactionGroupObject;
                        }

                        if (!is_object($reactionGroupData)) {
                            continue;
                        }

                        $reactionType = isset($reactionGroupData->type) ? $reactionGroupData->type : null;
                        $reactionCount = isset($reactionGroupData->count) ? $reactionGroupData->count : 0;
                        $reactionTooltip = isset($reactionGroupData->tooltip) ? $reactionGroupData->tooltip : '';

                        if (null === $reactionType) {
                            continue;
                        }
                        ?>

                        <div class="reaction" data-toggle="tooltip" data-original-title="<?php echo $reactionTooltip; ?>" data-placement="bottom">
                            <img src="img/reaction/<?php echo $reactionType; ?>.png" alt="">
                            <span><?php echo $reactionCount; ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($viewerIsAuthenticated && !$hasReactions) : ?>
                        <div class="reaction react-grey">
                            <img src="img/reaction/2.png" alt="">
                        </div>
                    <?php endif; ?>
                </a>

                <?php if ($viewerIsAuthenticated) : ?>
                    <div class="reaction-details">
                        <?php
                            render_template('partials/reactionChoiceList.php', array(
                                'giftId' => $giftId,
                                'reactionLabels' => $reactionLabels,
                            ));
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
        render_template('partials/giftModal.php', array(
            'gift' => $gift,
        ));
    ?>
</div>
