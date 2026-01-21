<ul class="reaction-choices">
    <?php for ($reactionIndex = 1; $reactionIndex <= 6; $reactionIndex++) : ?>
        <?php $reactionLabel = isset($reactionLabels[$reactionIndex]) ? $reactionLabels[$reactionIndex] : ''; ?>
        <li>
            <a data-toggle="tooltip" data-original-title="<?php echo $reactionLabel; ?>" data-add-reaction href="actions/addReaction.php?object=<?php echo $giftId; ?>&value=<?php echo $reactionIndex; ?>">
                <img src="img/reaction/<?php echo $reactionIndex; ?>.png" alt="">
            </a>
        </li>
    <?php endfor; ?>
</ul>
