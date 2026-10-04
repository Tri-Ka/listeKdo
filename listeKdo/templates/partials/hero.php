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
                        <img src="<?php echo e(avatar_url($owner)); ?>" alt="" width="132" height="132" data-tip="<?php echo e($owner['nom']); ?>" data-fallback="<?php echo e(avatar_default_url($owner)); ?>">

                        <?php if ($ctx['canEdit']) : ?>
                            <button type="button" class="round-btn round-btn--sm profile__action" data-open="<?php echo $ctx['isOwner'] ? 'profile-dialog' : 'child-dialog'; ?>" title="Modifier mon profil" aria-label="Modifier mon profil"><?php echo icon('pen'); ?></button>
                        <?php elseif ($ctx['me'] && $ctx['isFriend']) : ?>
                            <?php // Retirer de ses amis : action rare, donc une simple icône sur la photo (avec confirmation). ?>
                            <form class="profile__action-form" method="post" action="actions/removeFriend.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friendCode" value="<?php echo e($owner['code']); ?>">
                                <button type="submit" class="round-btn round-btn--sm profile__action profile__unfriend" aria-label="Retirer de mes amis" data-tip="Retirer de mes amis"
                                    data-confirm="<?php echo e($owner['nom']); ?> n'apparaîtra plus dans vos amis. Vous pourrez l'ajouter de nouveau quand vous voulez." data-confirm-title="Retirer de vos amis ?" data-confirm-ok="Retirer" data-confirm-icon="user-xmark"><?php echo icon('user-xmark'); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="profile__card">
                        <p class="profile__name"><?php echo e($owner['nom']); ?></p>
                        <?php // Une seule barre : nombre d'idées · badges (ouvre la vitrine) · liste privée. ?>
                        <div class="profile__meta">
                            <span class="profile__stat"><?php echo icon('gift'); ?> <span data-count-total><?php echo (int) $objectCount; ?></span> idée<?php echo 1 < $objectCount ? 's' : ''; ?></span>
                            <?php if (0 < $badges['count'] || ($ctx['canEdit'] && 0 < count($badges['showcase']))) : ?>
                                <button type="button" class="profile__stat profile__badges" data-open="badges-dialog" aria-label="Voir les badges (<?php echo (int) $badges['count']; ?>)">🏅 <?php echo (int) $badges['count']; ?></button>
                            <?php endif; ?>
                            <?php if ($ctx['private']) : ?>
                                <?php
                                if ($ctx['canEdit']) {
                                    $viewerCount = count(list_viewer_ids($ctx['owner']['id']));
                                    $privateTip = 'Visible seulement par ' . ($ctx['isOwner'] ? 'vous' : 'ses gestionnaires')
                                        . (0 < $viewerCount ? ' et ' . $viewerCount . ' ami' . (1 < $viewerCount ? 's' : '') . ' choisi' . (1 < $viewerCount ? 's' : '') : '');
                                } else {
                                    $privateTip = 'Liste privée : vous faites partie des amis invités à la voir';
                                }
                                ?>
                                <span class="profile__stat profile__private" data-tip="<?php echo e($privateTip); ?>"><?php echo icon('lock'); ?> Privée</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$ctx['canEdit'] && $ctx['me'] && !$ctx['isFriend']) : ?>
                            <form class="profile__friend" method="post" action="actions/addFriend.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friendCode" value="<?php echo e($owner['code']); ?>">
                                <button type="submit" class="btn btn--primary btn--sm"><?php echo icon('user-plus'); ?> Ajouter à mes amis</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="hero__titles">
                <h1 class="hero__title">
                    <img src="<?php echo e(asset('img/' . $theme['dir'] . '/title.png')); ?>" alt="<?php echo e($owner ? theme_text($theme, 'heading', $owner) : $theme['title']); ?>" width="460" height="215">
                </h1>
                <p class="hero__subtitle"><?php echo e($owner ? theme_text($theme, 'subtitle', $owner) : 'Créez votre liste de cadeaux et partagez-la avec vos proches.'); ?></p>

                <?php $next = $owner ? event_next($owner) : null; ?>
                <?php if (null !== $next) : ?>
                    <?php
                    // Valeurs initiales (le JS les met à jour chaque seconde, à l'heure du visiteur).
                    // Comme en JS : jours entiers jusqu'à la date + temps jusqu'à minuit (changement d'heure compris).
                    $midnight = mktime(0, 0, 0, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
                    $days = max(0, event_days($owner) - 1);
                    $left = max(0, min(86399, $midnight - time()));
                    $units = array(
                        'd' => array($days, 'jours'),
                        'h' => array(floor($left / 3600), 'heures'),
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

            </div>
        </div>

        <?php if ($owner && 0 < count($ctx['ownerChildren'])) : ?>
            <nav class="hero__children" aria-label="Listes gérées par <?php echo e($owner['nom']); ?>">
                <span>Voir aussi :</span>
                <?php foreach ($ctx['ownerChildren'] as $child) : ?>
                    <a class="child-chip" href="index.php?user=<?php echo e(rawurlencode($child['code'])); ?>">
                        <?php echo avatar($child, 'child-chip__avatar'); ?> <?php echo e($child['nom']); ?>
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

        <?php if ($owner && !$ctx['private']) : ?>
            <?php $url = share_url($owner); ?>
            <dialog class="modal modal--small share-dialog" id="share-dialog" aria-labelledby="share-title">
                <header class="modal__header">
                    <h2 id="share-title">Partager la liste</h2>
                    <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
                </header>
                <div class="modal__body share" data-share data-share-code="<?php echo e($owner['code']); ?>">
                <p class="share__label">Envoyez ce lien à vos proches : ils verront la liste sans avoir besoin de compte.</p>
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

                <?php $me = current_user(); ?>
                <?php if ($me && referral_enabled()) : ?>
                    <?php
                    $rewards = referral_rewards();
                    $code = referral_code($me);
                    $link = site_base_url() . '?parrain=' . $code;
                    ?>
                    <section class="referral referral--share" data-referral>
                        <div class="referral__intro">
                            <strong>Parrainez vos proches : +<?php echo (int) $rewards['sponsor']; ?> <?php echo gem_icon(); ?> par filleul</strong>
                            <span>Ils créent leur liste avec votre code<?php echo 0 < $rewards['welcome'] ? ' (+' . (int) $rewards['welcome'] . ' gemmes pour eux)' : ''; ?>. Vous gagnez les vôtres à leur première idée.</span>
                        </div>
                        <div class="referral__code">
                            <span class="referral__value"><?php echo e($code); ?></span>
                            <input type="hidden" value="<?php echo e($link); ?>" data-referral-link>
                            <button type="button" class="btn btn--light btn--sm" data-referral-copy><?php echo icon('link'); ?> Copier le lien</button>
                            <a class="btn btn--light btn--sm" href="https://wa.me/?text=<?php echo e(rawurlencode('Viens créer ta liste de cadeaux avec moi ! Mon code de parrainage : ' . $code . ' — ' . $link)); ?>" target="_blank" rel="noopener"><?php echo icon('whatsapp'); ?> WhatsApp</a>
                        </div>
                    </section>
                <?php endif; ?>
                </div>
            </dialog>
        <?php endif; ?>

    <?php echo render('partials/waves', array('id' => 'hero-waves', 'class' => 'waves--hero')); ?>
</section>
