<?php
/*
 * Réactions d'un commentaire (emojis libres) : pastille résumée, et liste de qui a réagi en la touchant.
 * Rechargé par actions/commentReaction.php.
 */
$reactions = isset($comment['reactions']) ? $comment['reactions'] : array();
$counts = array();
$mine = '';
$names = array();
foreach ($reactions as $reaction) {
    $counts[$reaction['emoji']] = isset($counts[$reaction['emoji']]) ? $counts[$reaction['emoji']] + 1 : 1;
    $names[] = $reaction['user']['nom'];
    if ($me && (int) $reaction['user_id'] === (int) $me['id']) {
        $mine = $reaction['emoji'];
    }
}
arsort($counts);
$total = count($reactions);
$listId = 'comment-reactors-' . (int) $comment['id'];
?>
<div class="comment-reactions" data-comment-reactions="<?php echo (int) $comment['id']; ?>" data-mine="<?php echo e($mine); ?>">
    <?php if (0 < $total) : ?>
        <button type="button" class="comment-reactions__summary<?php echo '' !== $mine ? ' is-mine' : ''; ?>" aria-expanded="false" aria-controls="<?php echo $listId; ?>" data-comment-reactors title="<?php echo e(implode(', ', $names)); ?>">
            <?php foreach (array_slice(array_keys($counts), 0, 3) as $emoji) : ?>
                <span class="comment-reactions__emoji"><?php echo e($emoji); ?></span>
            <?php endforeach; ?>
            <?php if (1 < $total) : ?>
                <span class="comment-reactions__total"><?php echo $total; ?></span>
            <?php endif; ?>
        </button>
        <ul class="comment-reactions__list" id="<?php echo $listId; ?>" hidden>
            <?php foreach ($reactions as $reaction) : ?>
                <?php $isMine = $me && (int) $reaction['user_id'] === (int) $me['id']; ?>
                <li>
                    <?php echo avatar($reaction['user'], 'comment-reactions__avatar'); ?>
                    <span class="comment-reactions__name"><?php echo $isMine ? 'Vous' : e($reaction['user']['nom']); ?></span>
                    <?php if ($isMine) : ?>
                        <button type="button" class="comment-reactions__remove" data-comment-reaction-remove>Retirer</button>
                    <?php endif; ?>
                    <span class="comment-reactions__emoji"><?php echo e($reaction['emoji']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
