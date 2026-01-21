<?php
    $sessionUser = null;
    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $sessionUser = $_SESSION['user'];
    }

    $giftListView = build_gift_list_view_model($sessionUser, $currentUser, $objects);

    $viewerContext = isset($giftListView->viewer) ? $giftListView->viewer : new stdClass();
    $giftItems = isset($giftListView->gifts) ? $giftListView->gifts : array();
    $reactionLabels = isset($giftListView->reaction_labels) ? $giftListView->reaction_labels : array();

    $viewerIsAuthenticated = isset($viewerContext->is_authenticated) ? $viewerContext->is_authenticated : false;
    $viewerIsOwner = isset($viewerContext->is_owner) ? $viewerContext->is_owner : false;
    $viewerIsGuest = isset($viewerContext->is_guest) ? $viewerContext->is_guest : false;
    $currentUserCode = isset($viewerContext->current_user_code) ? $viewerContext->current_user_code : '';

    $templateDir = dirname(__FILE__);

    include $templateDir . '/_connect.php';
    include $templateDir . '/_friendList.php';
?>

<div class="container">
    <div class="bs-docs-section clearfix">
        <?php include $templateDir . '/_top.php'; ?>
        <?php include $templateDir . '/_share.php'; ?>
        <?php include $templateDir . '/_notifs.php'; ?>

        <?php if ($currentUser) : ?>
            <?php include $templateDir . '/_list.php'; ?>
        <?php endif; ?>

        <?php if (!$sessionUser) : ?>
            <div class="row">
                <div class="col-xs-12 col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3">
                    <a class="btn btn-primary btn-block btn-lg" href="#" data-toggle="modal" data-target="#addUserModal" style="margin-top: 40px; margin-bottom: 40px;">
                        <i class="fa fa-plus"></i> Créer ma liste
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php include $templateDir . '/_bottom.php'; ?>
    </div>

    <?php if ($currentUser) : ?>
        <?php if ($sessionUser && isset($currentUser['id']) && $sessionUser['id'] === $currentUser['id']) : ?>
            <?php foreach ($objects as $object) : ?>
                <?php include $templateDir . '/modalEditObject.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
