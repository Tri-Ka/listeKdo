<?php
$canEdit = $ctx['canEdit'];
// La colonne « message » n'existe qu'après la migration sql/2026-10-01-mot-du-proprietaire.sql.
$hasMessage = array_key_exists('message', $owner);
$message = $hasMessage ? trim((string) $owner['message']) : '';
?>
<footer class="footer">
    <?php echo render('partials/waves', array('id' => 'footer-waves', 'class' => 'waves--footer')); ?>
    <div class="footer__inner">
        <?php if ($theme['footer']) : ?>
            <?php echo deco($theme, $theme['footer'], 'footer__deco'); ?>
        <?php endif; ?>

        <figure class="note">
            <blockquote class="note__text">
                <?php echo '' !== $message ? multiline($message) : e(theme_text($theme, 'note', $owner)); ?>
            </blockquote>
            <figcaption class="note__signature">
                <?php echo avatar($owner, 'note__avatar'); ?>
                <span><?php echo e($owner['nom']); ?></span>
            </figcaption>

            <?php if ($canEdit && $hasMessage) : ?>
                <button type="button" class="round-btn round-btn--sm note__edit" data-open="<?php echo $ctx['isOwner'] ? 'profile-dialog' : 'child-dialog'; ?>" data-focus="message"
                    title="Écrire mon petit mot" aria-label="Écrire mon petit mot"><?php echo icon('pen'); ?></button>
            <?php endif; ?>
        </figure>
    </div>
</footer>
