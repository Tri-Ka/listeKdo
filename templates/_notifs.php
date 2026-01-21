<?php
$notificationList = array();
if ($sessionUser && isset($sessionUser['notifications']) && is_array($sessionUser['notifications'])) {
    $notificationList = $sessionUser['notifications'];
}
?>

<?php if ($sessionUser) : ?>
    <div class="notifications" data-notif>
        <a class="toggle-notif" href="actions/updateSeenNotif.php" data-toggle-notif>
            <i class="fa fa-bell"></i>
        </a>

        <ul class="notification-list">
            <?php foreach ($notificationList as $notification) : ?>
                <?php
                $productUserCode = '';
                if (isset($notification['product_user']) && is_array($notification['product_user']) && isset($notification['product_user']['code'])) {
                    $productUserCode = $notification['product_user']['code'];
                }

                if ('' === $productUserCode) {
                    continue;
                }

                $notificationLinkId = isset($notification['product_id']) ? $notification['product_id'] : '';
                $authorId = isset($notification['user']['id']) ? $notification['user']['id'] : null;
                $authorName = isset($notification['user']['nom']) ? $notification['user']['nom'] : '';
                $notificationType = isset($notification['type']) ? (int) $notification['type'] : 0;
                $timePassed = isset($notification['timePassed']) ? $notification['timePassed'] : '';
                $productData = isset($notification['product']) && is_array($notification['product']) ? $notification['product'] : array();
                $productOwnerId = isset($productData['user_id']) ? $productData['user_id'] : null;
                $isNewNotification = isset($notification['new']) ? (bool) $notification['new'] : false;

                $authorAvatarStyle = '';
                if (null !== $authorId) {
                    $authorAvatarStyle = retrieveAvatarUrl($authorId);
                }

                $sessionUserId = ($sessionUser && isset($sessionUser['id'])) ? $sessionUser['id'] : null;
                ?>
                <li class="notification <?php echo $isNewNotification ? 'new' : ''; ?>">
                    <a data-notif-link href="index.php?user=<?php echo $productUserCode; ?>#idea-<?php echo $notificationLinkId; ?>">
                        <div class="comment-avatar" style="<?php echo $authorAvatarStyle; ?>"></div>

                        <div class="comment-content">
                            <?php if (1 === $notificationType) : ?>
                                <?php if (null !== $sessionUserId && $productOwnerId === $sessionUserId) : ?>
                                    <span class="comment-user"><?php echo $authorName; ?></span> a commenté votre idée !
                                <?php else : ?>
                                    <span class="comment-user"><?php echo $authorName; ?></span> a commenté une idée !
                                <?php endif; ?>
                            <?php elseif (2 === $notificationType) : ?>
                                <span class="comment-user"><?php echo $authorName; ?></span> a ajouté une nouvelle idée !
                            <?php elseif (3 === $notificationType) : ?>
                                <span class="comment-user"><?php echo $authorName; ?></span> a réagit à une idée !
                            <?php endif; ?>
                        </div>

                        <div class="notif-date">
                            <?php echo $timePassed; ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
