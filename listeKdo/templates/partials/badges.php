<?php
/*
 * Badges : vitrine de la liste affichée (#badges-dialog) et fête des nouveaux badges (#badge-new-dialog,
 * ouverte automatiquement par js/app.js). Voir lib/badges.php.
 */
$showcase = $badges['showcase'];
$new = $badges['new'];
$tiers = badge_tiers();
$mine = $ctx['me'] && $owner && (int) $ctx['me']['id'] === (int) $owner['id'];
?>
<?php if (0 < count($showcase)) : ?>
    <?php
    $groups = array('trophy' => array(), 'badge' => array());
    foreach ($showcase as $badge) {
        // Badge secret pas encore obtenu : caché aux autres, et en « ??? » pour soi.
        if ($badge['secret'] && !$badge['earned'] && !$ctx['canEdit']) {
            continue;
        }
        // Les visiteurs ne voient que ce qui est obtenu.
        if (!$badge['earned'] && !$ctx['canEdit']) {
            continue;
        }
        $groups['trophy' === $badge['kind'] ? 'trophy' : 'badge'][] = $badge;
    }
    ?>
    <dialog class="modal badges-dialog" id="badges-dialog" aria-labelledby="badges-title">
        <header class="modal__header">
            <h2 id="badges-title"><?php echo $mine ? 'Mes badges' : 'Badges de ' . e($owner['nom']); ?></h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <p class="badges__summary">
                <strong><?php echo (int) $badges['count']; ?></strong> obtenu<?php echo 1 < $badges['count'] ? 's' : ''; ?>
                <?php if ($ctx['canEdit']) : ?>sur <?php echo count($showcase); ?><?php endif; ?>
            </p>
            <?php foreach (array('trophy' => 'Trophées', 'badge' => 'Badges') as $kind => $label) : ?>
                <?php if (0 < count($groups[$kind])) : ?>
                    <h3 class="badges__heading"><?php echo $label; ?></h3>
                    <ul class="trophy-grid">
                        <?php foreach ($groups[$kind] as $badge) : ?>
                            <?php $hidden = $badge['secret'] && !$badge['earned']; ?>
                            <?php
                            $percent = !$badge['earned'] && null !== $badge['value'] ? (int) round(100 * $badge['value'] / max(1, (int) $badge['threshold'])) : 0;
                            $showProgress = !$badge['earned'] && !$hidden && null !== $badge['value'];
                            ?>
                            <li class="trophy-tile medal--<?php echo e($badge['tier']); ?><?php echo $badge['earned'] ? ' is-earned' : ' is-locked'; ?>"<?php echo $showProgress ? ' style="--progress: ' . $percent . '%"' : ''; ?>>
                                <span class="trophy-tile__medal<?php echo $showProgress ? ' has-progress' : ''; ?>" aria-hidden="true">
                                    <span class="trophy-tile__icon"><?php echo $hidden ? '❔' : e($badge['emoji']); ?></span>
                                </span>
                                <strong class="trophy-tile__name"><?php echo $hidden ? 'Badge secret' : e($badge['name']); ?></strong>
                                <small class="trophy-tile__desc"><?php echo $hidden ? 'À vous de le découvrir…' : e($badge['description']); ?></small>
                                <?php if ($badge['earned']) : ?>
                                    <span class="trophy-tile__chip"><?php echo isset($tiers[$badge['tier']]) ? e($tiers[$badge['tier']]) : ''; ?> · <?php echo e(date('d/m/Y', strtotime($badge['earned']))); ?></span>
                                <?php elseif ($showProgress) : ?>
                                    <span class="trophy-tile__count"><?php echo (int) $badge['value']; ?> / <?php echo (int) $badge['threshold']; ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </dialog>
<?php endif; ?>

<?php if (0 < count($new)) : ?>
    <dialog class="modal modal--small badge-new" id="badge-new-dialog" aria-labelledby="badge-new-title" data-badge-new>
        <div class="modal__body badge-new__body">
            <?php $first = $new[0]; ?>
            <span class="badge-new__medal medal--<?php echo e($first['tier']); ?>" aria-hidden="true"><?php echo e($first['emoji']); ?></span>
            <h2 id="badge-new-title" class="badge-new__title">
                <?php echo 1 === count($new) ? ('trophy' === $first['kind'] ? 'Nouveau trophée !' : 'Nouveau badge !') : count($new) . ' nouveaux badges !'; ?>
            </h2>
            <?php if (1 === count($new)) : ?>
                <p class="badge-new__name"><?php echo e($first['name']); ?></p>
                <p class="badge-new__text"><?php echo e($first['description']); ?></p>
            <?php else : ?>
                <ul class="badge-new__list">
                    <?php foreach (array_slice($new, 0, 8) as $badge) : ?>
                        <li class="medal--<?php echo e($badge['tier']); ?>" data-tip="<?php echo e($badge['name']); ?>"><span aria-hidden="true"><?php echo e($badge['emoji']); ?></span><span class="sr-only"><?php echo e($badge['name']); ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (8 < count($new)) : ?><p class="badge-new__text">et <?php echo count($new) - 8; ?> autres…</p><?php endif; ?>
            <?php endif; ?>
        </div>
        <footer class="modal__footer badge-new__footer">
            <?php if ($ctx['me'] && $owner && (int) $owner['id'] === (int) $ctx['me']['id']) : ?>
                <button type="button" class="btn btn--light" data-open="badges-dialog">Voir mes badges</button>
            <?php else : ?>
                <a class="btn btn--light" href="index.php?user=<?php echo e(rawurlencode($ctx['me']['code'])); ?>#badges">Voir mes badges</a>
            <?php endif; ?>
            <button type="button" class="btn btn--primary" data-close>Super !</button>
        </footer>
    </dialog>
<?php endif; ?>
