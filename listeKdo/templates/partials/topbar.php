<?php
$me = $ctx['me'];
$children = isset($ctx['children']) && is_array($ctx['children']) ? $ctx['children'] : array();
$myGifts = isset($myGifts) && is_array($myGifts) ? $myGifts : array();
// Liste privée qu'on ne peut pas voir : ni son nom ni sa photo dans la barre.
$owner = $ctx['canView'] ? $ctx['owner'] : null;
?>
<header class="topbar">
    <a class="topbar__brand" href="<?php echo $owner ? 'index.php?user=' . e(rawurlencode($owner['code'])) : 'index.php'; ?>">
        <span class="topbar__logo"><?php echo icon('gift'); ?></span>
        <?php if ($owner) : ?>
            <span class="topbar__owner">
                <?php echo avatar($owner, 'topbar__owner-avatar', false, ''); ?>
                <span class="topbar__owner-name"><?php echo e($ctx['isOwner'] ? 'Ma liste' : $owner['nom']); ?></span>
            </span>
        <?php endif; ?>
        <span class="topbar__text">
            <?php if ($owner) : ?>
                <small class="topbar__kicker"><?php echo e($theme['title']); ?></small>
                <span class="topbar__title"><?php echo e($owner['nom']); ?></span>
            <?php else : ?>
                <small class="topbar__kicker">Bienvenue sur</small>
                <span class="topbar__title">Liste de Kdo</span>
            <?php endif; ?>
        </span>
        <?php $days = $owner ? event_days($owner) : null; ?>
        <?php if (null !== $days) : ?>
            <span class="topbar__days" title="<?php echo e(event_label($owner)); ?>"><?php echo 0 === $days ? '🎉' : 'J-' . (int) $days; ?></span>
        <?php endif; ?>
    </a>

    <?php if ($me && 0 < count($friends)) : ?>
    <?php $hidden = count($friends) - 6; ?>
    <nav class="friends" aria-label="Mes amis">
        <ul>
            <?php foreach ($friends as $friend) : ?>
                <li>
                    <a href="index.php?user=<?php echo e(rawurlencode($friend['code'])); ?>" data-tip="<?php echo e($friend['nom']); ?>"
                        <?php echo $owner && (int) $owner['id'] === (int) $friend['id'] ? 'aria-current="page"' : ''; ?>>
                        <?php echo avatar($friend, 'friends__avatar', false, $friend['nom'] . ('' !== event_short($friend) ? ' · ' . event_short($friend) : '')); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="friends__more" data-open="friends-dialog" aria-label="Voir tous mes amis">
            <?php echo 0 < $hidden ? '+' . (int) $hidden : icon('ellipsis'); ?>
        </button>
    </nav>
    <?php endif; ?>

    <div class="topbar__actions">
        <?php if ($ctx['canGift']) : ?>
            <label class="pill-switch" title="Afficher les idées déjà offertes et qui les offre">
                <?php echo icon('gift'); ?>
                <span class="pill-switch__label">Voir les idées offertes</span>
                <input type="checkbox" role="switch" data-show-gifted>
            </label>
        <?php endif; ?>

        <?php if ($me && !$ctx['isOwner']) : ?>
            <a class="btn btn--light my-list" href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>" aria-label="Revenir à ma liste">
                <?php echo icon('gift'); ?> <span class="btn__label">Ma liste</span>
            </a>
        <?php endif; ?>

        <?php if ($me && 0 < count($friends)) : ?>
            <button type="button" class="round-btn friends-shortcut" data-open="friends-dialog" aria-label="Mes amis" data-tip="Mes amis"><?php echo icon('users'); ?></button>
        <?php endif; ?>

        <?php if ($me) : ?>
            <button type="button" class="round-btn bell" data-toggle-notifications aria-controls="notifications" aria-expanded="false" title="Notifications" aria-label="Notifications">
                <?php echo icon('bell'); ?>
                <?php if (0 < $newNotifications) : ?>
                    <span class="badge" data-notification-badge><?php echo $newNotifications > 9 ? '9+' : (int) $newNotifications; ?></span>
                <?php endif; ?>
            </button>

            <details class="user-menu" data-user-menu>
                <summary aria-label="Mon compte">
                    <?php echo avatar($me, 'user-menu__avatar', false); ?>
                </summary>
                <div class="user-menu__panel">
                    <p class="user-menu__name"><?php echo e($me['nom']); ?></p>
                    <a href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>"><?php echo icon('list'); ?> Ma liste</a>
                    <?php foreach ($children as $child) : ?>
                        <a href="index.php?user=<?php echo e(rawurlencode($child['code'])); ?>"><?php echo icon('layer-group'); ?> <?php echo e($child['nom']); ?></a>
                    <?php endforeach; ?>
                    <?php if (children_enabled()) : ?>
                        <button type="button" data-open="child-new-dialog"><?php echo icon('plus'); ?> Créer une liste secondaire</button>
                    <?php endif; ?>
                    <?php $giftCount = my_gifts_count($myGifts); ?>
                    <button type="button" data-open="my-gifts-dialog"><?php echo icon('gift'); ?> Les cadeaux que j'offre
                        <?php if (0 < $giftCount) : ?><span class="user-menu__count"><?php echo (int) $giftCount; ?></span><?php endif; ?></button>
                    <button type="button" data-open="profile-dialog"><?php echo icon('user'); ?> Mon profil</button>
                    <button type="button" data-open="extension-dialog"><?php echo icon('puzzle-piece'); ?> Extension Chrome</button>
                    <?php if (is_admin($me)) : ?>
                        <a href="admin.php"><?php echo icon('shield-halved'); ?> Administration</a>
                    <?php endif; ?>
                    <form method="post" action="actions/disconnect.php">
                        <?php echo csrf_field(); ?>
                        <button type="submit"><?php echo icon('right-from-bracket'); ?> Se déconnecter</button>
                    </form>
                </div>
            </details>
        <?php else : ?>
            <button type="button" class="btn btn--light" data-open="login-dialog" aria-label="Se connecter"><?php echo icon('circle-user'); ?> <span class="btn__label">Se connecter</span></button>
        <?php endif; ?>
    </div>
</header>

<?php if ($me && 0 < count($friends)) : ?>
    <dialog class="modal" id="friends-dialog" aria-labelledby="friends-title">
        <header class="modal__header">
            <h2 id="friends-title">Mes amis</h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <ul class="friends-grid">
                <?php foreach ($friends as $friend) : ?>
                    <li>
                        <a href="index.php?user=<?php echo e(rawurlencode($friend['code'])); ?>"
                            <?php echo $owner && (int) $owner['id'] === (int) $friend['id'] ? 'aria-current="page"' : ''; ?>>
                            <?php echo avatar($friend, 'friends-grid__avatar'); ?>
                            <span><?php echo e($friend['nom']); ?></span>
                            <?php if ('' !== event_short($friend)) : ?>
                                <small class="friends-grid__event"><?php echo icon('calendar-days'); ?> <?php echo e(event_short($friend)); ?></small>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </dialog>
<?php endif; ?>
