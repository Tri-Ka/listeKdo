<?php
    if (!isset($giftItems) || !is_array($giftItems)) {
        $giftItems = array();
    }

    if (!isset($viewerContext) || !is_object($viewerContext)) {
        $viewerContext = new stdClass();
    }

    $viewerIsAuthenticated = isset($viewerIsAuthenticated) ? $viewerIsAuthenticated : (isset($viewerContext->is_authenticated) ? $viewerContext->is_authenticated : false);
    $viewerIsOwner = isset($viewerIsOwner) ? $viewerIsOwner : (isset($viewerContext->is_owner) ? $viewerContext->is_owner : false);
    $viewerIsGuest = isset($viewerIsGuest) ? $viewerIsGuest : (isset($viewerContext->is_guest) ? $viewerContext->is_guest : false);
    $currentUserCode = isset($currentUserCode) ? $currentUserCode : (isset($viewerContext->current_user_code) ? $viewerContext->current_user_code : '');
    $reactionLabels = isset($reactionLabels) ? $reactionLabels : (isset($viewerContext->reaction_labels) ? $viewerContext->reaction_labels : array());
?>

<?php if ($viewerIsGuest) : ?>
    <div class="box-check-display">
        <label for="check-gifted">Voir les Kdos offerts / offrir un Kdo</label>
        <input data-switch-gifted name="check-gifted" class="apple-switch" type="checkbox">
    </div>
<?php endif; ?>

<div class="row">
    <div class="grid">
        <?php if ($viewerIsOwner) : ?>
            <div class="col-xs-12 col-sm-6 col-md-4 grid-item">
                <a class="panel panel-default panel-object btn-add" href="#" data-toggle="modal" data-target="#myModal" data-toggle2="tooltip" data-original-title="ajouter une idée Kdo">
                    <i class="fa fa-gift"></i>
                </a>
            </div>
        <?php endif; ?>

        <?php foreach ($giftItems as $giftData) : ?>
            <?php
                if (!is_object($giftData)) {
                    continue;
                }

                render_template('partials/giftCard.php', array(
                    'gift' => $giftData,
                    'reactionLabels' => $reactionLabels,
                ));
            ?>
        <?php endforeach; ?>
    </div>
</div>
