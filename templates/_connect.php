<?php
$sessionUserHasFriends = false;
$sessionUserAvatarStyle = '';
if ($sessionUser) {
    if (isset($sessionUser['friends']) && is_array($sessionUser['friends']) && count($sessionUser['friends']) > 0) {
        $sessionUserHasFriends = true;
    }

    if (isset($sessionUser['id'])) {
        $sessionUserAvatarStyle = retrieveAvatarUrl($sessionUser['id']);
    }
}
?>

<?php if ($sessionUser): ?>
    <div class="current-user">
        <div class="current-avatar" style="<?php echo $sessionUserAvatarStyle; ?>"></div>
        <a href="index.php?user=<?php echo $sessionUser['code']; ?>"><?php echo $sessionUser['nom']; ?></a>
        <a href="actions/disconnect.php" onclick="signOut();"><i class="fa fa-power-off"></i></a>
    </div>

    <?php if ($sessionUserHasFriends): ?>
        <ul class="current-user-friends">
            <?php foreach ($sessionUser['friends'] as $friend): ?>
                <?php
                $friendId = isset($friend['id']) ? $friend['id'] : null;
                $friendCode = isset($friend['code']) ? $friend['code'] : '';
                $friendName = isset($friend['nom']) ? $friend['nom'] : '';
                $friendAvatarStyle = '';
                if (null !== $friendId) {
                    $friendAvatarStyle = retrieveAvatarUrl($friendId);
                }
                ?>
                <li>
                    <a href="index.php?user=<?php echo $friendCode; ?>" data-toggle="tooltip" title="<?php echo $friendName; ?>" class="friend-img" style="<?php echo $friendAvatarStyle; ?>" data-placement="right"></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php else: ?>
    <div class="current-user">
        <a href="#" class="link-connect" href="#" data-toggle="modal" data-target="#connectModal">
            <i class="fa fa-power-off"></i> Se connecter
        </a>
    </div>
<?php endif; ?>
