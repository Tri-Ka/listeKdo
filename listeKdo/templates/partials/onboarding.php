<?php
// Visite guidée (js/app.js, « Visite guidée ») : les étapes sont décrites dans le JS, qui saute celles
// dont l'élément n'est pas à l'écran. Elle se lance seule sur sa propre liste tant qu'elle n'a pas été vue.
$me = $ctx['me'];
?>
<div class="tour" id="onboarding-tour" data-onboarding
    data-tour-name="<?php echo e($me['nom']); ?>"
    data-tour-home="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>"
    <?php echo $onHome ? ' data-tour-here' : ''; ?><?php echo $autoStart ? ' data-auto-start' : ''; ?> hidden>
    <div class="tour__backdrop" data-tour-backdrop></div>
    <div class="tour__spotlight" data-tour-spotlight aria-hidden="true"></div>
    <section class="tour__bubble" data-tour-bubble role="dialog" aria-modal="true" aria-labelledby="tour-title" tabindex="-1">
        <span class="tour__arrow" data-tour-arrow aria-hidden="true"></span>
        <button type="button" class="modal__close tour__close" data-tour-skip aria-label="Quitter la visite guidée"><?php echo icon('xmark'); ?></button>
        <div class="tour__head">
            <span class="tour__icon" data-tour-icon><?php echo icon('gift'); ?></span>
            <p class="tour__eyebrow" data-tour-eyebrow></p>
        </div>
        <h2 id="tour-title" data-tour-title></h2>
        <p class="tour__text" data-tour-text></p>
        <footer class="tour__footer">
            <span class="tour__progress" aria-hidden="true"><span data-tour-progress></span></span>
            <span class="tour__count" data-tour-count></span>
            <span class="tour__nav">
                <button type="button" class="btn btn--ghost btn--sm" data-tour-prev>Précédent</button>
                <button type="button" class="btn btn--primary btn--sm" data-tour-next>Suivant</button>
            </span>
        </footer>
    </section>
</div>
