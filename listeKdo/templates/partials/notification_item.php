<?php $states = notification_states_enabled(); ?>
<li class="notification<?php echo $notification['new'] ? ' notification--new' : ''; ?>" data-notification="<?php echo (int) $notification['id']; ?>">
    <a href="index.php?user=<?php echo e(rawurlencode($notification['owner_code'])); ?><?php echo NOTIF_EVENT == $notification['type'] ? '' : '#idea-' . (int) $notification['product_id']; ?>" data-notification-link>
        <?php echo avatar($notification['author'], 'notification__avatar'); ?>
        <span class="notification__text">
            <strong><?php echo e($notification['author']['nom']); ?></strong>
            <?php if (NOTIF_COMMENT == $notification['type']) : ?>
                a commenté <?php echo $notification['mine'] ? 'votre idée' : 'une idée'; ?>
            <?php elseif (NOTIF_NEW_IDEA == $notification['type']) : ?>
                a ajouté une nouvelle idée
            <?php elseif (NOTIF_EVENT == $notification['type']) : ?>
                <?php
                $when = array(1 => 'demain', 7 => 'dans une semaine', 30 => 'dans un mois');
                $tier = (int) $notification['product_id'];
                ?>
                <?php
                $what = array('birthday' => 'fête son anniversaire', 'naissance' => 'attend bébé', 'noel' => 'attend Noël');
                $emoji = array('birthday' => '🎂', 'naissance' => '👶', 'noel' => '🎄');
                $theme = isset($what[$notification['author']['theme']]) ? $notification['author']['theme'] : 'birthday';
                ?>
                <?php echo e($what[$theme] . ' ' . (isset($when[$tier]) ? $when[$tier] : 'bientôt')); ?> <?php echo $emoji[$theme]; ?>
                <small class="notification__hint">Une idée de cadeau ? Jetez un œil à sa liste</small>
            <?php else : ?>
                a réagi à <?php echo $notification['mine'] ? 'votre idée' : 'une idée'; ?>
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
