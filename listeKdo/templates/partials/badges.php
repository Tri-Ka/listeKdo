<?php
/*
 * Badges : vitrine de la liste affichée (#badges-dialog) et fête des nouveaux badges (#badge-new-dialog,
 * ouverte automatiquement par js/app.js). Voir lib/badges.php.
 */
$showcase = $badges['showcase'];
$new = $badges['new'];
$tiers = badge_tiers();
$mine = $ctx['me'] && $owner && (int) $ctx['me']['id'] === (int) $owner['id'];
$withGems = skins_enabled();
?>
<?php if (0 < count($showcase)) : ?>
    <?php
    // Onglets : obtenus, à débloquer (seulement pour ceux qui gèrent la liste), trophées.
    $earnedList = array();
    $lockedList = array();
    $trophies = array();
    $badgeGems = 0;
    $next = null;
    foreach ($showcase as $badge) {
        // Badge secret pas encore obtenu : caché aux autres, en « ??? » pour soi.
        if (!$badge['earned'] && !$ctx['canEdit']) {
            continue;
        }
        if ($badge['earned']) {
            $earnedList[] = $badge;
            $badgeGems += badge_gems($badge);
        } else {
            $lockedList[] = $badge;
            // Le plus proche d'être débloqué (hors secrets).
            if (!$badge['secret'] && null !== $badge['value']) {
                $ratio = $badge['value'] / max(1, (int) $badge['threshold']);
                if (null === $next || $ratio > $next['ratio']) {
                    $next = array('ratio' => $ratio, 'badge' => $badge);
                }
            }
        }
        if ('trophy' === $badge['kind']) {
            $trophies[] = $badge;
        }
    }
    // À débloquer : les plus avancés d'abord.
    usort($lockedList, 'badge_compare_progress');
    $tabs = array('earned' => array('Obtenus', $earnedList));
    if ($ctx['canEdit']) {
        $tabs['locked'] = array('À débloquer', $lockedList);
    }
    $tabs['trophy'] = array('Trophées', $trophies);
    $open = 0 < count($earnedList) || !$ctx['canEdit'] ? 'earned' : 'locked';
    $percent = (int) round(100 * count($earnedList) / max(1, $ctx['canEdit'] ? count($showcase) : count($earnedList)));
    ?>
    <dialog class="modal badges-dialog" id="badges-dialog" aria-labelledby="badges-title">
        <header class="badges-hero">
            <button type="button" class="modal__close badges-hero__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            <div class="badges-hero__intro">
                <?php echo avatar($owner, 'badges-hero__avatar', false, ''); ?>
                <div>
                    <h2 id="badges-title"><?php echo $mine ? 'Mes badges' : 'Badges de ' . e($owner['nom']); ?></h2>
                    <p><?php echo $mine ? 'Chaque action sur le site vous rapproche d\'un nouveau badge.' : 'Les badges et trophées obtenus par ' . e($owner['nom']) . '.'; ?></p>
                </div>
            </div>
            <div class="badges-hero__score">
                <span class="badges-hero__count"><strong><?php echo count($earnedList); ?></strong><?php if ($ctx['canEdit']) : ?><span>/ <?php echo count($showcase); ?></span><?php endif; ?></span>
                <span class="badges-hero__label">badge<?php echo 1 < count($earnedList) ? 's' : ''; ?> obtenu<?php echo 1 < count($earnedList) ? 's' : ''; ?></span>
            </div>
            <?php if ($ctx['canEdit']) : ?>
                <div class="badges-hero__progress">
                    <span class="badges-hero__bar" style="--done: <?php echo $percent; ?>%"></span>
                    <?php if ($next) : ?>
                        <span class="badges-hero__next">
                            Presque : <?php echo e($next['badge']['emoji']); ?> <strong><?php echo e($next['badge']['name']); ?></strong>
                            (<?php echo (int) $next['badge']['value']; ?>/<?php echo (int) $next['badge']['threshold']; ?>)
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($mine && isset($shop) && $shop) : ?>
                <button type="button" class="badges-hero__shop" data-open="shop-dialog">
                    <?php echo gem_icon('badges-hero__gem'); ?>
                    <span>
                        <small><?php echo (int) $badgeGems; ?> gemmes gagnées avec les badges</small>
                        Dépenser mes <?php echo (int) $shop['gems']['balance']; ?> gemmes
                    </span>
                    <?php echo icon('chevron-right'); ?>
                </button>
            <?php endif; ?>
        </header>

        <nav class="shop__tabs badges-tabs" aria-label="Badges">
            <?php foreach ($tabs as $key => $tab) : ?>
                <button type="button" class="shop__tab" data-badge-tab="<?php echo e($key); ?>" aria-pressed="<?php echo $key === $open ? 'true' : 'false'; ?>">
                    <?php echo e($tab[0]); ?> <span><?php echo count($tab[1]); ?></span>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="modal__body badges-body">
            <?php foreach ($tabs as $key => $tab) : ?>
                <section data-badge-section="<?php echo e($key); ?>"<?php echo $key !== $open ? ' hidden' : ''; ?>>
                    <?php if (0 === count($tab[1])) : ?>
                        <p class="badges-empty"><?php echo 'earned' === $key ? 'Pas encore de badge : ajoutez une idée, réagissez, offrez… ils arrivent vite !' : 'Rien ici pour le moment.'; ?></p>
                    <?php else : ?>
                        <ul class="trophy-grid">
                            <?php foreach ($tab[1] as $badge) : ?>
                                <?php echo render('partials/badge_tile', array('badge' => $badge, 'tiers' => $tiers, 'withGems' => $withGems)); ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
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
            <?php if ($withGems) : ?>
                <?php $won = 0; foreach ($new as $badge) { $won += badge_gems($badge); } ?>
                <p class="badge-new__gems">+<?php echo (int) $won; ?> <?php echo gem_icon(); ?> <span>gemmes à dépenser dans la boutique</span></p>
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
