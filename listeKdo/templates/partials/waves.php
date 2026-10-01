<?php
/*
 * Séparateur en vagues douces, superposées, dans la teinte du thème (couleurs : .waves dans css/app.css).
 * $id : préfixe unique pour les dégradés SVG ; $class : waves--hero ou waves--footer.
 */
?>
<svg class="waves <?php echo e($class); ?>" viewBox="0 0 1440 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <defs>
        <?php foreach (array('a', 'b', 'c') as $layer) : ?>
            <linearGradient id="<?php echo e($id . '-' . $layer); ?>" x1="0" x2="1" y1="0" y2="0">
                <stop offset="0" class="waves__<?php echo $layer; ?>1"/>
                <stop offset="1" class="waves__<?php echo $layer; ?>2"/>
            </linearGradient>
        <?php endforeach; ?>
    </defs>
    <path fill="url(#<?php echo e($id); ?>-a)" d="M0,38 C180,8 360,8 540,30 C720,52 900,62 1080,40 C1260,18 1350,22 1440,34 V100 H0 Z"/>
    <path fill="url(#<?php echo e($id); ?>-b)" d="M0,58 C200,40 380,72 600,62 C820,52 980,34 1180,50 C1300,60 1380,58 1440,52 V100 H0 Z"/>
    <path fill="url(#<?php echo e($id); ?>-c)" d="M0,78 C240,64 420,90 660,84 C900,78 1080,66 1280,76 C1360,80 1410,82 1440,80 V100 H0 Z"/>
</svg>
