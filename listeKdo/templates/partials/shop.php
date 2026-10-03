<?php
/*
 * Boutique (pastille de gemmes de la barre du haut, menu du compte) : gemmes gagnées avec les badges,
 * articles à acheter (un habillage pour un type de liste) et à porter sur sa liste. Voir lib/skins.php.
 * Un onglet par type de liste (js/app.js : [data-shop-tab]), celui de sa liste ouvert par défaut.
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

// Objectif : l'article le moins cher pas encore acheté pour le type de sa liste (sinon, n'importe lequel).
$goal = null;
foreach (array(isset($byTheme[$myTheme]) ? $byTheme[$myTheme] : array(), skin_items()) as $pool) {
    foreach ($pool as $key => $item) {
        if (!isset($owned[$key]) && (null === $goal || $item['price'] < $goal['price'])) {
            $goal = $item;
        }
    }
    if ($goal) {
        break;
    }
}
$goalPercent = $goal ? min(100, (int) round(100 * $gems['balance'] / max(1, $goal['price']))) : 100;
?>
<dialog class="modal shop" id="shop-dialog" aria-labelledby="shop-title">
    <header class="shop__hero">
        <button type="button" class="modal__close shop__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        <div class="shop__intro">
            <h2 id="shop-title">Boutique</h2>
            <p>Offrez un nouveau look à votre liste avec les gemmes gagnées grâce à vos badges.</p>
        </div>
        <div class="shop__wallet">
            <?php echo gem_icon('shop__wallet-gem'); ?>
            <span class="shop__wallet-text">
                <strong><?php echo (int) $gems['balance']; ?></strong>
                <span>gemme<?php echo 1 < $gems['balance'] ? 's' : ''; ?></span>
            </span>
        </div>
        <?php if ($goal) : ?>
            <div class="shop__goal">
                <span class="shop__goal-label">
                    <?php if (100 <= $goalPercent) : ?>
                        De quoi s'offrir <strong><?php echo e($goal['label']); ?> · <?php echo e($themes[$goal['theme']]['label']); ?></strong> !
                    <?php else : ?>
                        Prochain habillage : <strong><?php echo e($goal['label']); ?> · <?php echo e($themes[$goal['theme']]['label']); ?></strong>
                        — encore <?php echo (int) ($goal['price'] - $gems['balance']); ?> <?php echo gem_icon(); ?>
                    <?php endif; ?>
                </span>
                <span class="shop__goal-bar" style="--goal: <?php echo $goalPercent; ?>%"></span>
            </div>
        <?php endif; ?>
    </header>

    <nav class="shop__tabs" aria-label="Types de liste">
        <?php foreach ($sorted as $theme => $items) : ?>
            <?php $have = 0; foreach ($items as $key => $item) { if (isset($owned[$key])) $have++; } ?>
            <button type="button" class="shop__tab" data-shop-tab="<?php echo e($theme); ?>" aria-pressed="<?php echo $theme === $myTheme ? 'true' : 'false'; ?>">
                <?php echo e($themes[$theme]['label']); ?>
                <span><?php echo (int) $have; ?>/<?php echo count($items); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="modal__body shop__body">
        <?php foreach ($sorted as $theme => $items) : ?>
            <section class="shop__section" data-shop-section="<?php echo e($theme); ?>"<?php echo $theme !== $myTheme ? ' hidden' : ''; ?>>
                <?php if ($theme !== $myTheme) : ?>
                    <p class="shop__note">Pour vos listes « <?php echo e($themes[$theme]['label']); ?> » (la vôtre est « <?php echo e($themes[$myTheme]['label']); ?> ») : à choisir ensuite dans les paramètres de la liste.</p>
                <?php endif; ?>
                <ul class="shop__grid">
                    <?php if ($theme === $myTheme) : ?>
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

                    <?php foreach ($items as $key => $skin) : ?>
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
            </section>
        <?php endforeach; ?>

        <p class="shop__earn">Besoin de gemmes ? Chaque badge en rapporte : <a href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>#badges">voir mes badges</a>.</p>
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
