<?php $states = notification_states_enabled(); ?>
<li class="notification<?php echo $notification['new'] ? ' notification--new' : ''; ?>" data-notification="<?php echo (int) $notification['id']; ?>">
    <a href="index.php?user=<?php echo e(rawurlencode($notification['owner_code'])); ?><?php echo NOTIF_EVENT == $notification['type'] ? '' : '#idea-' . (int) $notification['product_id']; ?>" data-notification-link>
        <?php echo avatar($notification['author'], 'notification__avatar'); ?>
        <span class="notification__text">
            <strong><?php echo e($notification['author']['nom']); ?></strong>
            <?php if (NOTIF_COMMENT == $notification['type']) : ?>
                a commenté <?php echo $notification['mine'] ? 'votre idée' : 'une idée'; ?>
                <?php if ('' !== (string) $notification['product_nom']) : ?>
                    <small class="notification__hint"><?php echo e(legacy_text($notification['product_nom'])); ?></small>
                <?php endif; ?>
            <?php elseif (NOTIF_NEW_IDEA == $notification['type']) : ?>
                a ajouté une nouvelle idée
                <?php if ('' !== (string) $notification['product_nom']) : ?>
                    <small class="notification__hint"><?php echo e(legacy_text($notification['product_nom'])); ?></small>
                <?php endif; ?>
            <?php elseif (NOTIF_EVENT == $notification['type']) : ?>
                <?php
                $when = array(1 => 'demain', 7 => 'dans une semaine', 30 => 'dans un mois');
                $tier = (int) $notification['product_id'];
                ?>
                <?php
                $theme = theme_of($notification['author']);
                ?>
                <?php echo e($theme['reminder'] . ' ' . (isset($when[$tier]) ? $when[$tier] : 'bientôt')); ?> <?php echo $theme['emoji']; ?>
                <small class="notification__hint">Une idée de cadeau ? Jetez un œil à sa liste</small>
            <?php elseif (NOTIF_GIFT == $notification['type']) : ?>
                a réservé un cadeau pour <strong><?php echo e($notification['owner_nom']); ?></strong> 🎁
                <?php if ('' !== (string) $notification['product_nom']) : ?>
                    <small class="notification__hint"><?php echo e(legacy_text($notification['product_nom'])); ?></small>
                <?php endif; ?>
            <?php elseif (NOTIF_PARTICIPATION == $notification['type']) : ?>
                participe à un cadeau à plusieurs pour <strong><?php echo e($notification['owner_nom']); ?></strong> 🤝
                <?php if ('' !== (string) $notification['product_nom']) : ?>
                    <small class="notification__hint"><?php echo e(legacy_text($notification['product_nom'])); ?></small>
                <?php endif; ?>
            <?php else : ?>
                <?php $reactions = reaction_types(); $reaction = (int) $notification['reaction_type']; ?>
                a réagi
                <?php if (isset($reactions[$reaction])) : ?>
                    <img class="notification__reaction" src="img/reaction/<?php echo $reaction; ?>.png" alt="« <?php echo e($reactions[$reaction]); ?> »" width="18" height="18">
                <?php endif; ?>
                à <?php echo $notification['mine'] ? 'votre idée' : 'une idée'; ?>
                <?php if ('' !== (string) $notification['product_nom']) : ?>
                    <small class="notification__hint"><?php echo e(legacy_text($notification['product_nom'])); ?></small>
                <?php endif; ?>
            <?php endif; ?>
        </span>
        <time class="notification__date" datetime="<?php echo e($notification['created_at']); ?>"><?php echo e(time_ago($notification['created_at'])); ?></time>
    </a>
    <?php if ($states) : ?>
        <button type="button" class="notification__toggle" data-notification-toggle
            aria-label="<?php echo $notification['new'] ? 'Marquer comme lue' : 'Marquer comme non lue'; ?>"
            data-tip="<?php echo $notification['new'] ? 'Marquer comme lue' : 'Marquer comme non lue'; ?>"></button>
    <?php endif; ?>
</li>
