<?php
$owner = $ctx['owner'];
$themes = themes();
?>
<section class="hero">
    <div class="hero__deco hero__deco--left" aria-hidden="true">
        <?php foreach ($theme['left'] as $name) : ?>
            <?php echo deco($theme, $name); ?>
        <?php endforeach; ?>
    </div>

    <div class="hero__center">
        <div class="hero__head">
            <?php if ($owner) : ?>
                <div class="profile">
                    <div class="profile__avatar">
                        <img src="<?php echo e(avatar_url($owner)); ?>" alt="" width="132" height="132" data-tip="<?php echo e($owner['nom']); ?>" data-fallback="img/avatar-default.png">

                        <?php if ($ctx['canEdit']) : ?>
                            <button type="button" class="round-btn round-btn--sm profile__action" data-open="<?php echo $ctx['isOwner'] ? 'profile-dialog' : 'child-dialog'; ?>" title="Modifier mon profil" aria-label="Modifier mon profil"><?php echo icon('pen'); ?></button>
                        <?php elseif ($ctx['me']) : ?>
                            <form method="post" action="actions/<?php echo $ctx['isFriend'] ? 'removeFriend' : 'addFriend'; ?>.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friendCode" value="<?php echo e($owner['code']); ?>">
                                <?php if ($ctx['isFriend']) : ?>
                                    <button type="submit" class="round-btn round-btn--sm profile__action" title="Retirer de mes amis" aria-label="Retirer de mes amis" data-confirm="Retirer <?php echo e($owner['nom']); ?> de vos amis ?"><?php echo icon('user-xmark'); ?></button>
                                <?php else : ?>
                                    <button type="submit" class="round-btn round-btn--sm round-btn--primary profile__action" title="Ajouter à mes amis" aria-label="Ajouter à mes amis"><?php echo icon('user-plus'); ?></button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p class="profile__name"><?php echo e($owner['nom']); ?></p>
                    <p class="profile__meta"><?php echo icon('gift'); ?> <span data-count-total><?php echo (int) $objectCount; ?></span> idée<?php echo 1 < $objectCount ? 's' : ''; ?></p>
                </div>
            <?php endif; ?>

            <div class="hero__titles">
                <h1 class="hero__title">
                    <img src="<?php echo e(asset('img/deco/' . $theme['key'] . '/title.png')); ?>" alt="<?php echo e($owner ? theme_text($theme, 'heading', $owner) : $theme['title']); ?>" width="460" height="215">
                </h1>
                <p class="hero__subtitle"><?php echo e($owner ? theme_text($theme, 'subtitle', $owner) : 'Créez votre liste de cadeaux et partagez-la avec vos proches.'); ?></p>

                <?php $next = $owner ? event_next($owner) : null; ?>
                <?php if (null !== $next) : ?>
                    <?php
                    // Valeurs initiales (le JS les met à jour chaque seconde, à l'heure du visiteur).
                    $left = max(0, $next - time());
                    $units = array(
                        'd' => array(floor($left / 86400), 'jours'),
                        'h' => array(floor($left % 86400 / 3600), 'heures'),
                        'm' => array(floor($left % 3600 / 60), 'min'),
                        's' => array($left % 60, 'sec'),
                    );
                    ?>
                    <div class="countdown<?php echo 0 === event_days($owner) ? ' is-today' : ''; ?>" data-countdown="<?php echo e(date('Y-m-d', $next)); ?>" role="timer" aria-label="<?php echo e(event_label($owner)); ?>">
                        <?php $age = event_age($owner); ?>
                        <span class="countdown__label"><?php echo icon('gift'); ?> <?php echo e(event_name($owner)); ?><?php echo null !== $age ? ' · ' . (int) $age . ' ans' : ''; ?> dans</span>
                        <span class="countdown__units">
                            <?php foreach ($units as $key => $unit) : ?>
                                <span class="countdown__unit countdown__unit--<?php echo $key; ?>">
                                    <span class="countdown__value" data-unit="<?php echo $key; ?>"><?php echo 'd' === $key ? (int) $unit[0] : sprintf('%02d', $unit[0]); ?></span>
                                    <small><?php echo $unit[1]; ?></small>
                                </span>
                            <?php endforeach; ?>
                        </span>
                        <span class="countdown__today">C'est aujourd'hui ! 🎉</span>
                    </div>
                <?php endif; ?>

                <?php if ($ctx['canEdit']) : ?>
                    <form class="theme-picker" method="post" action="actions/changeTheme.php" aria-label="Thème de la liste">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="owner" value="<?php echo e($owner['code']); ?>">
                        <?php foreach ($themes as $key => $info) : ?>
                            <button type="submit" name="theme" value="<?php echo e($key); ?>" class="theme-picker__option theme-picker__option--<?php echo e($key); ?>"
                                aria-pressed="<?php echo $key === $theme['key'] ? 'true' : 'false'; ?>"><?php echo e($info['label']); ?></button>
                        <?php endforeach; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($owner) : ?>
            <?php $url = share_url($owner); ?>
            <div class="share" data-share data-share-code="<?php echo e($owner['code']); ?>">
                <p class="share__label">Partager cette liste avec vos amis</p>
                <div class="share__row">
                    <div class="share__link">
                        <input type="text" readonly value="<?php echo e($url); ?>" aria-label="Lien de la liste" data-share-url>
                        <button type="button" class="btn btn--primary btn--sm" data-copy><?php echo icon('link'); ?> Copier</button>
                    </div>
                    <div class="share__buttons">
                        <a class="round-btn share--whatsapp" href="https://wa.me/?text=<?php echo e(rawurlencode(theme_text($theme, 'heading', $owner) . ' : ' . $url)); ?>" target="_blank" rel="noopener" title="WhatsApp" aria-label="Partager sur WhatsApp"><?php echo icon('whatsapp'); ?></a>
                        <a class="round-btn share--facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e(rawurlencode($url)); ?>" target="_blank" rel="noopener" title="Facebook" aria-label="Partager sur Facebook"><?php echo icon('facebook-f'); ?></a>
                        <a class="round-btn share--x" href="https://x.com/intent/post?url=<?php echo e(rawurlencode($url)); ?>&amp;text=<?php echo e(rawurlencode(theme_text($theme, 'heading', $owner))); ?>" target="_blank" rel="noopener" title="X" aria-label="Partager sur X"><?php echo icon('x-twitter'); ?></a>
                        <button type="button" class="round-btn" data-native-share hidden title="Plus d'options" aria-label="Plus d'options de partage"><?php echo icon('ellipsis'); ?></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($owner && 0 < count($ctx['ownerChildren'])) : ?>
            <nav class="hero__children" aria-label="Listes gérées par <?php echo e($owner['nom']); ?>">
                <span>Voir aussi :</span>
                <?php foreach ($ctx['ownerChildren'] as $child) : ?>
                    <a class="child-chip" href="index.php?user=<?php echo e(rawurlencode($child['code'])); ?>">
                        <?php echo avatar($child, 'child-chip__avatar'); ?> Liste de <?php echo e($child['nom']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>

    <div class="hero__deco hero__deco--right" aria-hidden="true">
        <?php foreach ($theme['right'] as $name) : ?>
            <?php echo deco($theme, $name); ?>
        <?php endforeach; ?>
    </div>

    <?php echo render('partials/waves', array('id' => 'hero-waves', 'class' => 'waves--hero')); ?>
</section>
