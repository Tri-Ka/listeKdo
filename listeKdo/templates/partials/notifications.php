<?php
$hasMore = count($notifications) > NOTIFICATIONS_PER_PAGE;
$notifications = array_slice($notifications, 0, NOTIFICATIONS_PER_PAGE);
$states = notification_states_enabled();
?>
<aside class="drawer" id="notifications" data-notifications data-states="<?php echo $states ? '1' : '0'; ?>" hidden>
    <header class="drawer__header">
        <h2>Notifications</h2>
        <button type="button" class="icon-btn icon-btn--ghost" data-toggle-notifications aria-label="Fermer"><?php echo icon('xmark'); ?></button>
    </header>

    <?php if ($states) : ?>
        <div class="drawer__tools">
            <div class="drawer__tabs" role="tablist">
                <button type="button" role="tab" aria-selected="true" data-notification-filter="all">Toutes</button>
                <button type="button" role="tab" aria-selected="false" data-notification-filter="unread">Non lues <span data-unread-count><?php echo (int) $newNotifications; ?></span></button>
            </div>
            <button type="button" class="link-btn" data-mark-all-read<?php echo 0 === $newNotifications ? ' hidden' : ''; ?>><?php echo icon('check'); ?> Tout marquer comme lu</button>
        </div>
    <?php endif; ?>

    <ul class="notifications" data-notification-list>
        <?php foreach ($notifications as $notification) : ?>
            <?php echo render('partials/notification_item', array('notification' => $notification)); ?>
        <?php endforeach; ?>
        <?php if ($hasMore) : ?>
            <li class="notifications__more">
                <button type="button" class="btn btn--soft" data-more-notifications data-offset="<?php echo NOTIFICATIONS_PER_PAGE; ?>">Voir plus</button>
            </li>
        <?php endif; ?>
    </ul>
    <p class="drawer__empty" data-notifications-empty<?php echo 0 < count($notifications) ? ' hidden' : ''; ?>>Rien de nouveau pour l'instant.</p>
</aside>
