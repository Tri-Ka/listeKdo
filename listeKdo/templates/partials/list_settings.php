<?php
/*
 * « Paramètres de la liste » (roue dentée de la barre du haut) : titre, type de liste, date, liste privée.
 * Pour sa propre liste comme pour une liste secondaire gérée ($owner).
 */
$isMine = (int) $owner['id'] === (int) $ctx['me']['id'];
$theme = theme_of($owner);
?>
<dialog class="modal" id="list-settings-dialog" aria-labelledby="list-settings-title">
    <form method="post" action="actions/editList.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="owner" value="<?php echo e($owner['code']); ?>">
        <header class="modal__header">
            <h2 id="list-settings-title">Paramètres de la liste</h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body form">
            <?php if (list_title_enabled()) : ?>
                <?php
                // Titre par défaut (sans le titre choisi), affiché en exemple.
                $default = $owner;
                $default['list_title'] = '';
                ?>
                <label class="field">
                    <span>Titre de la liste <small>(facultatif)</small></span>
                    <input type="text" name="list_title" maxlength="120" value="<?php echo e(list_title($owner)); ?>" placeholder="<?php echo e(theme_text($theme, 'heading', $default)); ?>">
                </label>
            <?php endif; ?>
            <?php echo render('partials/theme_field', array('current' => $owner['theme'])); ?>
            <?php if (isset($shop) && $shop) : ?>
                <?php
                $currentSkin = isset($owner['skin']) ? (string) $owner['skin'] : '';
                // Articles achetés pour ce type de liste (changer le type plus haut demande d'enregistrer d'abord).
                $choices = array();
                foreach (skin_items() as $key => $item) {
                    if (isset($shop['owned'][$key]) && $item['theme'] === $owner['theme']) {
                        $choices[$key] = $item['label'];
                    }
                }
                ?>
                <label class="field">
                    <span>Habillage <small>(achetés dans la boutique pour ce type de liste)</small></span>
                    <select name="skin">
                        <option value="">Classique</option>
                        <?php foreach ($choices as $key => $label) : ?>
                            <option value="<?php echo e($key); ?>"<?php echo $key === $currentSkin ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (0 === count($choices)) : ?>
                        <small class="field__hint">Aucun habillage pour ce type de liste : <button type="button" class="link-btn" data-open="shop-dialog">ouvrir la boutique</button>.</small>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            <?php if (event_dates_enabled()) : ?>
                <label class="field">
                    <span>Date de l'événement <small>(<?php echo $isMine ? 'ma date de naissance pour un anniversaire' : 'date de naissance pour un anniversaire'; ?>, la date du mariage ou de la naissance prévue ; Noël tombe toujours le 25/12)</small></span>
                    <input type="date" name="event_date" value="<?php echo e($owner['event_date'] && '0000-00-00' !== $owner['event_date'] ? $owner['event_date'] : ''); ?>">
                </label>
            <?php endif; ?>
            <?php if (private_enabled()) : ?>
                <label class="switch">
                    <input type="hidden" name="is_private" value="0">
                    <input type="checkbox" name="is_private" value="1" role="switch"<?php echo !empty($owner['is_private']) ? ' checked' : ''; ?>>
                    <span>
                        <strong>Liste privée</strong>
                        <small><?php echo $isMine ? 'Visible par vous seul : vos amis ne la voient plus, même avec le lien' : 'Visible seulement par ses gestionnaires, même avec le lien'; ?></small>
                    </span>
                </label>
            <?php endif; ?>
        </div>
        <footer class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close>Annuler</button>
            <button type="submit" class="btn btn--primary">Enregistrer</button>
        </footer>
    </form>
</dialog>
