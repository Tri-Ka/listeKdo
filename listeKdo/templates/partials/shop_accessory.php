<?php
/*
 * Aperçu d'un accessoire de la boutique (templates/partials/shop.php), avec les vraies classes CSS :
 * sa propre photo dans le cadre, ou un petit compte à rebours avec l'effet. $key vide = sans accessoire.
 */
?>
<?php if ('frame' === $kind) : ?>
    <?php $catalog = frames(); ?>
    <span class="frame-host<?php echo !empty($catalog[$key]['image']) ? ' frame-host--img' : ''; ?>">
        <img class="frame-host__img" src="<?php echo e(avatar_url($me)); ?>" alt="" loading="lazy" data-fallback="<?php echo e(avatar_default_url($me)); ?>">
        <?php echo frame_html($key); ?>
    </span>
<?php else : ?>
    <span class="countdown countdown--mini"<?php echo '' !== $key ? ' data-fx="' . e($key) . '"' : ''; ?>>
        <?php echo countdown_fx_html($key); ?>
        <span class="countdown__units">
            <?php foreach (array('d' => array('12', 'jours'), 'h' => array('08', 'heures'), 'm' => array('45', 'min'), 's' => array('30', 'sec')) as $unit => $value) : ?>
                <span class="countdown__unit countdown__unit--<?php echo $unit; ?>">
                    <span class="countdown__value"><?php echo $value[0]; ?></span>
                    <small><?php echo $value[1]; ?></small>
                </span>
            <?php endforeach; ?>
        </span>
    </span>
<?php endif; ?>
