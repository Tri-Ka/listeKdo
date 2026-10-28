<?php
/*
 * Visionneuse d'images (commentaires) : ouverte par-dessus la fenêtre en cours pour tout lien [data-image-viewer].
 * Un clic sur l'image, le fond ou la croix la ferme (js/modules/comments.js).
 */
?>
<dialog class="viewer" id="image-viewer" aria-label="Image en grand">
    <button type="button" class="viewer__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
    <img class="viewer__image" src="" alt="" data-close>
</dialog>
