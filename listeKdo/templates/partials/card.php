<?php
$id = (int) $object['id'];
$gifted = $object['complete'];
$commentCount = count($object['comments']);
$received = $object['received'];
// Suggestion d'un ami (jamais affichée au propriétaire) : modifiable par son auteur et par les gestionnaires,
// comme suggestion_editable(), mais sans requête par vignette.
$suggestion = !empty($object['suggestion']);
$suggester = $suggestion && !empty($object['suggested_by_user']) ? $object['suggested_by_user'] : null;
$editable = $ctx['canEdit'];
if ($suggestion) {
    $mySuggestion = $ctx['me'] && (int) $object['suggested_by'] === (int) $ctx['me']['id'] && !empty($ctx['canSuggest']);
    $editable = ($ctx['canEdit'] && !$ctx['isOwner']) || $mySuggestion;
}
?>
<article class="card<?php echo $ctx['canGift'] && $gifted ? ' card--gifted' : ''; ?><?php echo $received ? ' card--received' : ''; ?><?php echo $suggestion ? ' card--suggestion' : ''; ?>" id="idea-<?php echo $id; ?>"
    data-object="<?php echo $id; ?>"
    data-suggestion="<?php echo $suggestion ? '1' : '0'; ?>"
    data-favorite="<?php echo $object['favorite'] ? '1' : '0'; ?>"
    data-received="<?php echo $received ? '1' : '0'; ?>"
    data-price="<?php echo null !== $object['price'] ? e($object['price']) : ''; ?>"
    <?php if ($ctx['canGift']) : ?>data-gifted="<?php echo $gifted ? '1' : '0'; ?>"<?php endif; ?>>

    <div class="card__media">
        <button type="button" class="card__image" data-open="object-<?php echo $id; ?>" aria-label="Voir le détail de <?php echo e($object['nom']); ?>">
            <img src="<?php echo e('' !== $object['image'] ? $object['image'] : 'img/idea-default.svg'); ?>" alt="" loading="lazy" decoding="async" data-fallback="img/idea-default.svg">
        </button>

        <?php // Les deux étiquettes sont toujours là : .card--received choisit laquelle s'affiche (« J'ai reçu » sans recharger). ?>
        <?php if ($ctx['canEdit'] && received_enabled()) : ?>
            <span class="card__badge card__badge--received"><?php echo icon('circle-check'); ?> Reçu</span>
        <?php endif; ?>
        <?php if ($suggestion) : ?>
            <span class="card__badge card__badge--idea card__badge--suggestion">
                <?php if ($suggester) : ?>
                    <span class="suggester"><?php echo avatar($suggester, 'suggester__avatar'); ?><?php echo icon('lightbulb', 'suggester__bulb'); ?></span>
                <?php else : ?>
                    <?php echo icon('lightbulb'); ?>
                <?php endif; ?>
                Suggestion<?php echo $suggester ? ' de ' . e($suggester['nom']) : ''; ?></span>
        <?php else : ?>
            <span class="card__badge card__badge--idea"><?php echo icon($object['is_group'] ? 'users' : 'gift'); ?>
                <?php echo $object['is_collection'] ? 'Collection · ' . ($ctx['canEdit'] ? count($object['items']) : (int) $object['items_total']) : ($object['is_group'] && $ctx['canGift'] ? 'À plusieurs' : 'Idée cadeau'); ?></span>
        <?php endif; ?>
        <?php if ($ctx['canGift']) : ?>
            <span class="card__badge card__badge--gifted gift-only"><?php echo icon('circle-check'); ?> Déjà offert</span>
        <?php endif; ?>

        <?php echo render('partials/favorite', array('object' => $object, 'ctx' => $ctx)); ?>

        <?php if (null !== $object['price']) : ?>
            <span class="card__price"><?php echo e(format_price($object['price'])); ?></span>
        <?php endif; ?>

    </div>

    <div class="card__body">
        <h2 class="card__title"><?php echo e($object['nom']); ?></h2>
        <?php if ('' !== $object['description']) : ?>
            <p class="card__text"><?php echo e(excerpt(plain_text($object['description']), 220)); ?></p>
        <?php endif; ?>

        <?php if ($object['is_collection']) : ?>
            <?php echo render('partials/items', array('object' => $object, 'ctx' => $ctx, 'limit' => 4)); ?>
        <?php endif; ?>
    </div>

    <footer class="card__actions">
        <button type="button" class="round-btn" data-open="object-<?php echo $id; ?>" title="Commentaires" aria-label="Commentaires">
            <?php echo icon('comment-o'); ?>
            <span class="round-btn__count" data-comment-count="<?php echo $id; ?>"<?php echo 0 === $commentCount ? ' hidden' : ''; ?>><?php echo $commentCount; ?></span>
        </button>

        <?php if ('' !== $object['link']) : ?>
            <a class="round-btn" href="<?php echo e($object['link']); ?>" target="_blank" rel="noopener nofollow" title="Voir sur le site" aria-label="Voir sur le site"><?php echo icon('cart-shopping'); ?></a>
        <?php endif; ?>

        <div class="reactions" data-reactions-slot="<?php echo $id; ?>">
            <?php echo render('partials/reactions', array('object' => $object, 'ctx' => $ctx)); ?>
        </div>

        <div class="card__cta">
            <?php if ($ctx['canGift'] && !$received) : ?>
                <div class="gift-slot" data-gift-slot="<?php echo $id; ?>">
                    <?php echo render('partials/gift', array('object' => $object, 'ctx' => $ctx)); ?>
                </div>
            <?php elseif (!$editable && '' !== $object['link']) : ?>
                <a class="btn btn--primary" href="<?php echo e($object['link']); ?>" target="_blank" rel="noopener nofollow">Voir <?php echo icon('chevron-right'); ?></a>
            <?php endif; ?>

            <?php if ($editable) : ?>
                <?php
                $editData = kdo_json(array(
                    'id' => $id,
                    'nom' => $object['nom'],
                    'description' => $object['description'],
                    'image' => $object['image'],
                    'link' => $object['link'],
                    'price' => null !== $object['price'] ? str_replace('.', ',', (string) $object['price']) : '',
                    'items' => $object['items'] ? array_map('item_for_form', $object['items']) : array(),
                ));
                ?>
                <details class="card-menu" data-card-menu>
                    <summary class="round-btn" aria-label="Modifier, marquer comme reçu, supprimer" data-tip="Modifier…"><?php echo icon('ellipsis'); ?></summary>
                    <div class="card-menu__panel">
                        <button type="button" data-open-object-form="<?php echo e($editData); ?>"><?php echo icon('pen'); ?> Modifier</button>
                        <?php if (received_enabled() && $ctx['canEdit']) : ?>
                            <form method="post" action="actions/toggleReceived.php" data-ajax="received">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <button type="submit" class="received-btn" aria-pressed="<?php echo $received ? 'true' : 'false'; ?>">
                                    <?php echo icon($received ? 'arrow-up-right-from-square' : 'box-archive'); ?>
                                    <span><?php echo $received ? 'Remettre dans la liste' : "Je l'ai reçu"; ?></span>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="actions/deleteObject.php" data-delete-idea>
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <button type="submit" class="card-menu__danger"
                                data-confirm="Elle disparaîtra de la liste, avec ses commentaires et ses réactions." data-confirm-title="Supprimer cette idée ?" data-confirm-ok="Supprimer" data-confirm-icon="trash-can"><?php echo icon('trash-can'); ?> Supprimer</button>
                        </form>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </footer>
</article>
