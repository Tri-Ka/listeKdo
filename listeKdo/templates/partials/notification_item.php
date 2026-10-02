<?php $states = notification_states_enabled(); ?>
<li class="notification<?php echo $notification['new'] ? ' notification--new' : ''; ?>" data-notification="<?php echo (int) $notification['id']; ?>">
    <a href="index.php?user=<?php echo e(rawurlencode($notification['owner_code'])); ?><?php echo NOTIF_EVENT == $notification['type'] ? '' : (NOTIF_BADGE == $notification['type'] ? '#badges' : '#idea-' . (int) $notification['product_id']); ?>" data-notification-link>
        <?php echo avatar($notification['author'], 'notification__avatar'); ?>
        <span class="notification__text">
            <?php if ($notification['self'] && NOTIF_BADGE == $notification['type']) : ?>
                <strong>Bravo !</strong>
            <?php else : ?>
                <strong><?php echo e($notification['author']['nom']); ?></strong>
            <?php endif; ?>
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
            <?php elseif (NOTIF_BADGE == $notification['type']) : ?>
                <?php
                // Ses propres badges : « Vous avez obtenu… » (l'auteur est soi-même).
                $badge = $notification['badge'];
                $self = $notification['self'];
                $what = 'trophy' === $badge['kind'] ? 'le trophée' : 'le badge';
                ?>
                <?php if (1 < $badge['count']) : ?>
                    <?php echo $self ? 'Vous avez' : 'a'; ?> obtenu <?php echo (int) $badge['count']; ?> badges, dont <?php echo e($badge['emoji']); ?> <strong><?php echo e($badge['name']); ?></strong>
                <?php else : ?>
                    <?php echo $self ? 'Vous avez' : 'a'; ?> obtenu <?php echo $what; ?> <?php echo e($badge['emoji']); ?> <strong><?php echo e($badge['name']); ?></strong>
                <?php endif; ?>
                <small class="notification__hint"><?php echo $self ? 'Voir mes badges' : 'Voir ses badges'; ?></small>
            <?php elseif (NOTIF_GIFT == $notification['type']) : ?>
                a réservé un cadeau pour <strong><?php echo e($notification['owner_nom']); ?></strong> 🎁
                <?php if ('' !== (string) $notification['product_nom']) : ?>
                    <small class="notification__hint"><?php echo e(legacy_text($notification['product_nom'])); ?></small>
                <?php endif; ?>
            <?php elseif (NOTIF_PARTICIPATION == $notification['type']) : ?>
                <?php
                // État actuel de la cagnotte (notifications_add_groups) : lancée, avancée ou complète.
                $group = isset($notification['group']) ? $notification['group'] : null;
                $complete = $group && null !== $group['price'] && $group['total'] >= $group['price'];
                $progress = array();
                if ('' !== (string) $notification['product_nom']) $progress[] = legacy_text($notification['product_nom']);
                if ($group) {
                    $progress[] = $group['count'] . ' participant' . (1 < $group['count'] ? 's' : '');
                    if (null !== $group['price']) $progress[] = format_price($group['total']) . ' / ' . format_price($group['price']);
                }
                ?>
                <?php echo $group && $group['started'] ? 'a lancé une cagnotte' : 'a participé à la cagnotte'; ?>
                pour <strong><?php echo e($notification['owner_nom']); ?></strong>
                <?php echo $complete ? ' : elle est complète 🎉' : ' 🤝'; ?>
                <?php if (0 < count($progress)) : ?>
                    <small class="notification__hint"><?php echo e(implode(' · ', $progress)); ?></small>
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
