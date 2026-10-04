<?php
/*
 * Boutique (pastille de gemmes de la barre du haut, menu du compte) : gemmes gagnées avec les badges,
 * articles à acheter (un habillage pour un type de liste) et à porter sur sa liste. Voir lib/skins.php.
 * Trois catégories (js/app.js : [data-shop-cat]) : habillages, cadres, compte à rebours. Les habillages ont un
 * sous-onglet par type de liste ([data-shop-tab]), celui de sa liste ouvert par défaut. Articles groupés par rareté.
 * L'habillage d'une liste secondaire se choisit dans ses « Paramètres de la liste ».
 */
$gems = $shop['gems'];
$owned = $shop['owned'];
$rarities = skin_rarities();
$themes = themes();
$current = isset($me['skin']) ? (string) $me['skin'] : '';
$myTheme = isset($themes[$me['theme']]) ? $me['theme'] : 'noel';

// Articles groupés par type de liste, celui de sa liste en premier.
$byTheme = array();
foreach (skin_items() as $key => $item) {
    $byTheme[$item['theme']][$key] = $item;
}
$sorted = isset($byTheme[$myTheme]) ? array($myTheme => $byTheme[$myTheme]) : array();
foreach ($byTheme as $t => $list) {
    if ($t !== $myTheme) {
        $sorted[$t] = $list;
    }
}

// Cadres et effets de compte à rebours (onglets après les types de liste).
$accessories = array();
if (accessories_enabled()) {
    foreach (accessory_items() as $id => $item) {
        $accessories[$item['kind']][$id] = $item;
    }
}
$kinds = accessory_kinds();
?>
<dialog class="modal shop" id="shop-dialog" aria-labelledby="shop-title">
    <header class="shop__hero">
        <button type="button" class="modal__close shop__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        <div class="shop__intro">
            <h2 id="shop-title">Boutique</h2>
            <p>Dépensez vos gemmes pour habiller votre liste et votre photo.</p>
        </div>
        <div class="shop__wallet">
            <?php echo gem_icon('shop__wallet-gem'); ?>
            <span class="shop__wallet-text">
                <strong><?php echo (int) $gems['balance']; ?></strong>
                <span>gemme<?php echo 1 < $gems['balance'] ? 's' : ''; ?></span>
            </span>
        </div>
    </header>

    <?php
    $skinsTotal = 0;
    $skinsHave = 0;
    foreach ($sorted as $theme => $items) {
        foreach ($items as $key => $item) {
            $skinsTotal++;
            if (isset($owned[$key])) $skinsHave++;
        }
    }
    $cats = array('skins' => array('Habillages', 'palette', $skinsHave, $skinsTotal));
    foreach ($accessories as $kind => $items) {
        $have = 0;
        foreach ($items as $id => $item) {
            if (isset($owned[$id])) $have++;
        }
        $cats[$kind] = array($kinds[$kind]['label'], 'frame' === $kind ? 'circle-user' : 'calendar-days', $have, count($items));
    }
    ?>
    <?php if (1 < count($cats)) : ?>
        <nav class="shop__cats" aria-label="Catégories">
            <?php foreach ($cats as $cat => $info) : ?>
                <button type="button" class="shop__cat" data-shop-cat="<?php echo e($cat); ?>" aria-pressed="<?php echo 'skins' === $cat ? 'true' : 'false'; ?>">
                    <span class="shop__cat-icon"><?php echo icon($info[1]); ?></span>
                    <span class="shop__cat-text"><strong><?php echo e($info[0]); ?></strong><small><?php echo (int) $info[2]; ?>/<?php echo (int) $info[3]; ?> dans ma collection</small></span>
                </button>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <nav class="shop__tabs" aria-label="Types de liste" data-shop-subnav>
        <?php foreach ($sorted as $theme => $items) : ?>
            <?php $have = 0; foreach ($items as $key => $item) { if (isset($owned[$key])) $have++; } ?>
            <button type="button" class="shop__tab" data-shop-tab="<?php echo e($theme); ?>" aria-pressed="<?php echo $theme === $myTheme ? 'true' : 'false'; ?>">
                <?php if (!empty($themes[$theme]['footer'])) : ?>
                    <img class="shop__tab-thumb" src="<?php echo e(asset('img/deco/' . $theme . '/' . $themes[$theme]['footer'] . '.png')); ?>" alt="" loading="lazy">
                <?php endif; ?>
                <span class="shop__tab-label"><?php echo e($themes[$theme]['label']); ?></span>
                <span class="shop__tab-count"><?php echo (int) $have; ?>/<?php echo count($items); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="modal__body shop__body">
        <div data-shop-panel="skins">
        <?php foreach ($sorted as $theme => $items) : ?>
            <section class="shop__section" data-shop-section="<?php echo e($theme); ?>"<?php echo $theme !== $myTheme ? ' hidden' : ''; ?>>
                <?php $first = true; ?>
                <?php foreach (shop_by_rarity($items) as $rarity => $group) : ?>
                <h3 class="shop__rarity shop__rarity--<?php echo e($rarity); ?>"><?php echo e($rarities[$rarity]); ?> <span><?php echo count($group); ?></span></h3>
                <ul class="shop__grid">
                    <?php if ($first && $theme === $myTheme) : ?>
                        <?php // Habillage d'origine, toujours disponible. ?>
                        <li class="shop-item shop-item--classic<?php echo '' === $current ? ' is-worn' : ''; ?>">
                            <div class="shop-item__preview">
                                <img src="<?php echo e(asset('img/deco/' . $myTheme . '/title.png')); ?>" alt="" loading="lazy">
                            </div>
                            <div class="shop-item__body">
                                <strong class="shop-item__name">Classique</strong>
                                <span class="shop-item__desc">L'habillage d'origine, offert.</span>
                            </div>
                            <form method="post" action="actions/shopEquip.php" class="shop-item__action">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="skin" value="">
                                <?php if ('' === $current) : ?>
                                    <span class="shop-item__state"><?php echo icon('circle-check'); ?> Sur ma liste</span>
                                <?php else : ?>
                                    <button type="submit" class="btn btn--light btn--sm">Utiliser</button>
                                <?php endif; ?>
                            </form>
                        </li>
                    <?php endif; ?>
                    <?php $first = false; ?>

                    <?php foreach ($group as $key => $skin) : ?>
                        <?php
                        $has = isset($owned[$key]);
                        $worn = $key === $current;
                        $missing = $skin['price'] - $gems['balance'];
                        $c = $skin['colors'];
                        // Aperçu (js/app.js, #skin-preview-dialog) : titre, décorations et couleurs de l'habillage.
                        $base = $themes[$theme];
                        $base['key'] = $theme;
                        $look = skin_apply($base, array('skin' => $key));
                        $deco = array();
                        foreach (array('left', 'right') as $side) {
                            $deco[$side] = array();
                            foreach ($look[$side] as $name) {
                                $deco[$side][] = asset('img/' . $look['dir'] . '/' . $name . '.png');
                            }
                        }
                        $preview = array(
                            'name' => $skin['label'],
                            'type' => $themes[$theme]['label'],
                            'rarity' => $rarities[$skin['rarity']],
                            'title' => asset('img/' . $look['dir'] . '/title.png'),
                            'subtitle' => theme_text($base, 'subtitle', $me),
                            'left' => $deco['left'],
                            'right' => $deco['right'],
                            'colors' => $c,
                        );
                        ?>
                        <li class="shop-item shop-item--<?php echo e($skin['rarity']); ?><?php echo $has ? ' is-owned' : ''; ?><?php echo $worn ? ' is-worn' : ''; ?><?php echo !$has && 0 >= $missing ? ' is-affordable' : ''; ?>"
                            style="--sk-brand: <?php echo e($c['brand']); ?>; --sk-hero: <?php echo e($c['hero-from']); ?>; --sk-page: <?php echo e($c['page']); ?>; --sk-c1: <?php echo e($c['confetti-1']); ?>; --sk-c2: <?php echo e($c['confetti-2']); ?>; --sk-c3: <?php echo e($c['confetti-3']); ?>">
                            <div class="shop-item__preview">
                                <button type="button" class="shop-item__zoom" data-skin-preview="<?php echo e(kdo_json($preview)); ?>" aria-label="Aperçu de « <?php echo e($skin['label']); ?> »">
                                    <span class="shop-item__zoom-label"><?php echo icon('eye'); ?> Aperçu</span>
                                </button>
                                <img src="<?php echo e(asset('img/skins/' . $skin['skin'] . '/' . $theme . '/title.png')); ?>" alt="" loading="lazy">
                                <span class="shop-item__rarity"><?php echo e($rarities[$skin['rarity']]); ?></span>
                                <?php if ($has) : ?>
                                    <span class="shop-item__owned" title="Dans votre collection"><?php echo icon('circle-check'); ?></span>
                                <?php endif; ?>
                                <span class="shop-item__swatch" aria-hidden="true"><i></i><i></i><i></i></span>
                            </div>
                            <div class="shop-item__body">
                                <strong class="shop-item__name"><?php echo e($skin['label']); ?></strong>
                                <span class="shop-item__desc"><?php echo e($skin['description']); ?></span>
                            </div>
                            <form method="post" action="actions/<?php echo $has ? 'shopEquip' : 'shopBuy'; ?>.php" class="shop-item__action">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="skin" value="<?php echo e($key); ?>">
                                <?php if ($worn) : ?>
                                    <span class="shop-item__state"><?php echo icon('circle-check'); ?> Sur ma liste</span>
                                <?php elseif ($has && $theme === $myTheme) : ?>
                                    <button type="submit" class="btn btn--light btn--sm">Utiliser</button>
                                <?php elseif ($has) : ?>
                                    <span class="shop-item__state shop-item__state--owned"><?php echo icon('circle-check'); ?> Dans ma collection</span>
                                <?php elseif (0 < $missing) : ?>
                                    <span class="shop-item__price is-short"><?php echo gem_icon(); ?> <?php echo (int) $skin['price']; ?></span>
                                    <span class="shop-item__missing">encore <?php echo (int) $missing; ?></span>
                                <?php else : ?>
                                    <button type="submit" class="shop-item__buy"
                                        data-confirm="L'habillage « <?php echo e($skin['label']); ?> » pour les listes « <?php echo e($themes[$theme]['label']); ?> » rejoindra votre collection<?php echo $theme === $myTheme ? ' et habillera aussitôt votre liste' : ''; ?>." data-confirm-title="Acheter pour <?php echo (int) $skin['price']; ?> gemmes ?" data-confirm-ok="Acheter" data-confirm-icon="gift">
                                        <?php echo gem_icon(); ?> <?php echo (int) $skin['price']; ?>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
        </div>

        <?php foreach ($accessories as $kind => $items) : ?>
            <?php $worn = accessory_worn($me, $kind); ?>
            <section class="shop__section" data-shop-panel="<?php echo e($kind); ?>" hidden>
                <?php $first = true; ?>
                <?php foreach (shop_by_rarity($items) as $rarity => $group) : ?>
                <h3 class="shop__rarity shop__rarity--<?php echo e($rarity); ?>"><?php echo e($rarities[$rarity]); ?> <span><?php echo count($group); ?></span></h3>
                <ul class="shop__grid">
                    <?php if ($first) : ?>
                    <?php // Sans cadre / sans effet : toujours disponible. ?>
                    <li class="shop-item shop-item--classic<?php echo '' === $worn ? ' is-worn' : ''; ?>">
                        <div class="shop-item__preview shop-item__preview--<?php echo e($kind); ?>">
                            <?php echo render('partials/shop_accessory', array('kind' => $kind, 'key' => '', 'me' => $me)); ?>
                        </div>
                        <div class="shop-item__body">
                            <strong class="shop-item__name"><?php echo 'frame' === $kind ? 'Sans cadre' : 'Classique'; ?></strong>
                            <span class="shop-item__desc"><?php echo 'frame' === $kind ? 'Votre photo, toute simple.' : 'Le compte à rebours d\'origine.'; ?></span>
                        </div>
                        <form method="post" action="actions/shopWear.php" class="shop-item__action">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="kind" value="<?php echo e($kind); ?>">
                            <input type="hidden" name="item" value="">
                            <?php if ('' === $worn) : ?>
                                <span class="shop-item__state"><?php echo icon('circle-check'); ?> <?php echo 'frame' === $kind ? 'Sur ma photo' : 'Sur ma liste'; ?></span>
                            <?php else : ?>
                                <button type="submit" class="btn btn--light btn--sm">Utiliser</button>
                            <?php endif; ?>
                        </form>
                    </li>
                    <?php endif; ?>
                    <?php $first = false; ?>

                    <?php foreach ($group as $id => $item) : ?>
                        <?php
                        $has = isset($owned[$id]);
                        $isWorn = $item['key'] === $worn;
                        $missing = $item['price'] - $gems['balance'];
                        ?>
                        <li class="shop-item shop-item--<?php echo e($item['rarity']); ?><?php echo $has ? ' is-owned' : ''; ?><?php echo $isWorn ? ' is-worn' : ''; ?><?php echo !$has && 0 >= $missing ? ' is-affordable' : ''; ?>" data-shop-item="<?php echo e($id); ?>">
                            <div class="shop-item__preview shop-item__preview--<?php echo e($kind); ?>">
                                <?php echo render('partials/shop_accessory', array('kind' => $kind, 'key' => $item['key'], 'me' => $me)); ?>
                                <span class="shop-item__rarity"><?php echo e($rarities[$item['rarity']]); ?></span>
                                <?php if ($has) : ?>
                                    <span class="shop-item__owned" title="Dans votre collection"><?php echo icon('circle-check'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="shop-item__body">
                                <strong class="shop-item__name"><?php echo e($item['label']); ?></strong>
                                <span class="shop-item__desc"><?php echo e($item['description']); ?></span>
                            </div>
                            <form method="post" action="actions/<?php echo $has ? 'shopWear' : 'shopBuy'; ?>.php" class="shop-item__action">
                                <?php echo csrf_field(); ?>
                                <?php if ($has) : ?>
                                    <input type="hidden" name="kind" value="<?php echo e($kind); ?>">
                                    <input type="hidden" name="item" value="<?php echo e($item['key']); ?>">
                                <?php else : ?>
                                    <input type="hidden" name="skin" value="<?php echo e($id); ?>">
                                <?php endif; ?>
                                <?php if ($isWorn) : ?>
                                    <span class="shop-item__state"><?php echo icon('circle-check'); ?> <?php echo 'frame' === $kind ? 'Sur ma photo' : 'Sur ma liste'; ?></span>
                                <?php elseif ($has) : ?>
                                    <button type="submit" class="btn btn--light btn--sm">Utiliser</button>
                                <?php elseif (0 < $missing) : ?>
                                    <span class="shop-item__price is-short"><?php echo gem_icon(); ?> <?php echo (int) $item['price']; ?></span>
                                    <span class="shop-item__missing">encore <?php echo (int) $missing; ?></span>
                                <?php else : ?>
                                    <button type="submit" class="shop-item__buy"
                                        data-confirm="<?php echo e('frame' === $kind ? 'Le cadre « ' . $item['label'] . ' » entourera aussitôt votre photo.' : 'L\'effet « ' . $item['label'] . ' » animera aussitôt le compte à rebours de votre liste.'); ?>" data-confirm-title="Acheter pour <?php echo (int) $item['price']; ?> gemmes ?" data-confirm-ok="Acheter" data-confirm-icon="gift">
                                        <?php echo gem_icon(); ?> <?php echo (int) $item['price']; ?>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <?php if (referral_enabled()) : ?>
            <?php
            $rewards = referral_rewards();
            $code = referral_code($me);
            $link = referral_url($me);
            $refs = referral_counts($me['id']);
            ?>
            <section class="referral" data-referral data-short="referral">
                <div class="referral__intro">
                    <strong>Parrainez vos proches : +<?php echo (int) $rewards['sponsor']; ?> <?php echo gem_icon(); ?> par filleul</strong>
                    <span>Ils s'inscrivent avec votre code<?php echo 0 < $rewards['welcome'] ? ' (et reçoivent ' . (int) $rewards['welcome'] . ' gemmes)' : ''; ?>. Vous gagnez vos gemmes dès qu'ils ajoutent leur première idée.</span>
                </div>
                <div class="referral__code">
                    <span class="referral__value"><?php echo e($code); ?></span>
                    <input type="hidden" value="<?php echo e($link); ?>" data-referral-link>
                    <button type="button" class="btn btn--light btn--sm" data-referral-copy><?php echo icon('link'); ?> Copier le lien</button>
                    <a class="btn btn--light btn--sm" href="https://wa.me/?text=<?php echo e(rawurlencode('Viens créer ta liste de cadeaux avec moi ! Mon code de parrainage : ' . $code . ' — ' . $link)); ?>" target="_blank" rel="noopener"><?php echo icon('whatsapp'); ?> WhatsApp</a>
                </div>
                <span class="referral__stats">
                    <?php echo (int) $refs['active']; ?> filleul<?php echo 1 < $refs['active'] ? 's' : ''; ?> actif<?php echo 1 < $refs['active'] ? 's' : ''; ?>
                    <?php if ($refs['total'] > $refs['active']) : ?> · <?php echo (int) ($refs['total'] - $refs['active']); ?> en attente de leur première idée<?php endif; ?>
                    <?php if (0 < $gems['referral']) : ?> · <?php echo (int) $gems['referral']; ?> <?php echo gem_icon(); ?> gagnées<?php endif; ?>
                </span>
            </section>
        <?php endif; ?>

        <div class="shop__earn">
            <p><strong>Comment gagner des gemmes ?</strong> Vos badges vous en ont rapporté <?php echo (int) $gems['badges']; ?>, vos actions <?php echo (int) $gems['actions']; ?><?php echo 0 < $gems['referral'] ? ', le parrainage ' . (int) $gems['referral'] : ''; ?>.</p>
            <ul class="shop__rates">
                <?php foreach (gem_action_rates() as $key => $rate) : ?>
                    <?php if (0 < $rate) : ?>
                        <?php $actions = gem_actions(); ?>
                        <li><?php echo e($actions[$key][0]); ?> <b>+<?php echo (int) $rate; ?> <?php echo gem_icon(); ?></b></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li><a href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>#badges">Chaque badge</a> <b>+5 à +200 <?php echo gem_icon(); ?></b></li>
            </ul>
        </div>
    </div>
</dialog>

<?php // Aperçu d'un habillage : une fausse liste à ses couleurs, remplie par js/app.js. ?>
<dialog class="modal skin-preview" id="skin-preview-dialog" aria-labelledby="skin-preview-title">
    <div class="skin-preview__stage" data-preview-stage>
        <div class="skin-preview__bar">
            <span class="skin-preview__brand"><?php echo icon('gift'); ?> <?php echo e($me['nom']); ?></span>
            <span class="skin-preview__dots"><i></i><i></i><i></i></span>
        </div>
        <div class="skin-preview__hero">
            <div class="skin-preview__deco skin-preview__deco--left" data-preview-left></div>
            <div class="skin-preview__center">
                <img class="skin-preview__title" alt="" data-preview-title>
                <p class="skin-preview__subtitle" data-preview-subtitle></p>
                <span class="skin-preview__countdown"><b>12</b><b>08</b><b>45</b><b>30</b></span>
            </div>
            <div class="skin-preview__deco skin-preview__deco--right" data-preview-right></div>
        </div>
        <div class="skin-preview__tabs"><span class="is-on"><?php echo icon('gift'); ?> Toutes</span><span><?php echo icon('heart'); ?> Coups de cœur</span></div>
        <div class="skin-preview__cards"><i></i><i></i><i></i></div>
    </div>
    <footer class="skin-preview__footer">
        <div>
            <strong id="skin-preview-title" data-preview-name></strong>
            <span data-preview-meta></span>
        </div>
        <span class="skin-preview__price" data-preview-price hidden></span>
        <button type="button" class="btn btn--ghost" data-close>Fermer</button>
        <button type="button" class="shop-item__buy skin-preview__buy" data-preview-buy hidden></button>
    </footer>
</dialog>
