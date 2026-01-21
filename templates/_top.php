<?php
$currentUserAvatarStyle = '';
$sessionUserOwnsProfile = false;
$sessionUserCanToggleFriend = false;
$sessionUserHasFriendship = false;

if ($currentUser && isset($currentUser['id'])) {
    $currentUserAvatarStyle = retrieveAvatarUrl($currentUser['id']);
}

if ($sessionUser && $currentUser && isset($currentUser['id'])) {
    if (isset($sessionUser['id']) && $sessionUser['id'] === $currentUser['id']) {
        $sessionUserOwnsProfile = true;
    } else {
        $sessionUserCanToggleFriend = true;

        if (isset($sessionUser['friends']) && is_array($sessionUser['friends'])) {
            foreach ($sessionUser['friends'] as $friend) {
                if (isset($friend['code']) && isset($currentUser['code']) && $friend['code'] === $currentUser['code']) {
                    $sessionUserHasFriendship = true;
                    break;
                }
            }
        }
    }
}
?>

<div class="text-center header" style="margin-bottom: 30px;">
    <div class="row">
        <div class="col-xs-12 col-md-3 col-sm-6 col-sm-offset-3 col-md-offset-0">
            <?php if ($currentUser): ?>
                <div class="avatar" width="150px" style="<?php echo $currentUserAvatarStyle; ?>">
                    <?php if ($sessionUserCanToggleFriend): ?>
                        <?php if (!$sessionUserHasFriendship): ?>
                            <a href="actions/addFriend.php?friendCode=<?php echo $currentUser['code']; ?>" class="btn-add-friend" data-toggle="tooltip" title="ajouter cet ami"><i class="fa fa-user-plus"></i></a>
                        <?php else: ?>
                            <a href="actions/removeFriend.php?friendCode=<?php echo $currentUser['code']; ?>" class="btn-add-friend" data-toggle="tooltip" title="retirer cet ami"><i class="fa fa-user-times"></i></a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($sessionUserOwnsProfile): ?>
                        <a href="#" class="btn-add-friend" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></a>
                    <?php endif; ?>
                </div>

                <div class="name">
                    <?php echo $currentUser['nom']; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-xs-12 col-md-4 col-md-offset-1">
            <h1>
                <img src="img/<?php echo $currentTheme['label']; ?>/title.png" title="Ma Liste de Noël">
            </h1>
        </div>

        <div class="col-xs-12">
            <img class="img-head" src="img/<?php echo $currentTheme['label']; ?>/head.png" width="350px">
        </div>
    </div>
</div>
