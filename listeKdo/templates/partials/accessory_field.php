<?php
/*
 * Choix d'un accessoire acheté dans la boutique ($kind : frame ou countdown), parmi ceux de sa collection
 * ($owned : achats de la personne connectée, clé « frame/… »), avec un aperçu réel (partials/shop_accessory).
 * Cadre : dans « Mon profil » (actions/editUser.php). Compte à rebours : dans « Paramètres de la liste » (actions/editList.php).
 */
$kinds = accessory_kinds();
$field = $kinds[$kind]['column'];
$current = (string) $current;
?>
<fieldset class="field skin-field accessory-field accessory-field--<?php echo e($kind); ?>">
    <legend><?php echo 'frame' === $kind ? 'Cadre de ma photo' : 'Compte à rebours'; ?> <small>(achetés dans la boutique)</small></legend>
    <div class="skin-field__track">
        <label class="skin-field__option">
            <input type="radio" name="<?php echo e($field); ?>" value=""<?php echo '' === $current ? ' checked' : ''; ?>>
            <span class="skin-field__preview accessory-field__preview">
                <?php echo render('partials/shop_accessory', array('kind' => $kind, 'key' => '', 'me' => $me)); ?>
            </span>
            <span class="skin-field__name"><?php echo 'frame' === $kind ? 'Sans cadre' : 'Classique'; ?></span>
        </label>
        <?php foreach ($kinds[$kind]['catalog'] as $key => $item) : ?>
            <?php if (isset($owned[$kind . '/' . $key])) : ?>
                <label class="skin-field__option">
                    <input type="radio" name="<?php echo e($field); ?>" value="<?php echo e($key); ?>"<?php echo $key === $current ? ' checked' : ''; ?>>
                    <span class="skin-field__preview accessory-field__preview">
                        <?php echo render('partials/shop_accessory', array('kind' => $kind, 'key' => $key, 'me' => $me)); ?>
                    </span>
                    <span class="skin-field__name"><?php echo e($item['label']); ?></span>
                </label>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="button" class="skin-field__shop" data-open="shop-dialog" data-shop-open="<?php echo e($kind); ?>">
            <?php echo gem_icon('skin-field__gem'); ?>
            <span><?php echo 'frame' === $kind ? 'Plus de cadres' : 'Plus d\'effets'; ?></span>
        </button>
    </div>
</fieldset>
