<?php
/*
 * Fenêtre de confirmation, à la place de confirm() du navigateur.
 * Ouverte par js/app.js pour tout bouton ou lien avec data-confirm="Message", et en option :
 *   data-confirm-title (titre), data-confirm-ok (libellé du bouton), data-confirm-icon (icône du sprite).
 */
?>
<dialog class="modal modal--small confirm" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-text">
    <form method="dialog">
        <div class="modal__body confirm__body">
            <span class="confirm__icon" aria-hidden="true">
                <svg class="icon"><use href="<?php echo e(asset('img/icons.svg')); ?>#i-triangle-exclamation" data-confirm-icon></use></svg>
            </span>
            <h2 class="confirm__title" id="confirm-title" data-confirm-title>Vous êtes sûr ?</h2>
            <p class="confirm__text" id="confirm-text" data-confirm-text></p>
        </div>
        <footer class="modal__footer confirm__footer">
            <button type="button" class="btn btn--ghost" data-close autofocus>Annuler</button>
            <button type="submit" class="btn btn--danger" value="ok" data-confirm-ok>Confirmer</button>
        </footer>
    </form>
</dialog>
