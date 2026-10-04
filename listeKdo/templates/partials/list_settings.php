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
                // Habillages achetés, tous types confondus : js/app.js n'affiche que ceux du type coché au-dessus.
                $currentSkin = isset($owner['skin']) ? (string) $owner['skin'] : '';
                $allThemes = themes();
                $owned = array();
                foreach (skin_items() as $key => $item) {
                    if (isset($shop['owned'][$key])) {
                        $owned[$key] = $item;
                    }
                }
                ?>
                <fieldset class="field skin-field" data-skin-field>
                    <legend>Habillage <small>(achetés dans la boutique, pour le type de liste choisi)</small></legend>
                    <div class="skin-field__track">
                        <?php foreach ($allThemes as $t => $info) : ?>
                            <label class="skin-field__option skin-field__option--classic" data-skin-theme="<?php echo e($t); ?>"<?php echo $t !== $owner['theme'] ? ' hidden' : ''; ?>>
                                <input type="radio" name="skin" value=""<?php echo $t === $owner['theme'] && '' === $currentSkin ? ' checked' : ''; ?><?php echo $t !== $owner['theme'] ? ' disabled' : ''; ?>>
                                <span class="skin-field__preview"><img src="<?php echo e(asset('img/deco/' . $t . '/title.png')); ?>" alt="" loading="lazy"></span>
                                <span class="skin-field__name">Classique</span>
                            </label>
                        <?php endforeach; ?>
                        <?php foreach ($owned as $key => $item) : ?>
                            <?php $mineTheme = $item['theme'] === $owner['theme']; ?>
                            <label class="skin-field__option" data-skin-theme="<?php echo e($item['theme']); ?>"<?php echo $mineTheme ? '' : ' hidden'; ?> style="--preview-bg: <?php echo e($item['colors']['hero-from']); ?>; --ring: <?php echo e($item['colors']['brand']); ?>">
                                <input type="radio" name="skin" value="<?php echo e($key); ?>"<?php echo $key === $currentSkin ? ' checked' : ''; ?><?php echo $mineTheme ? '' : ' disabled'; ?>>
                                <span class="skin-field__preview"><img src="<?php echo e(asset('img/skins/' . $item['skin'] . '/' . $item['theme'] . '/title.png')); ?>" alt="" loading="lazy"></span>
                                <span class="skin-field__name"><?php echo e($item['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <button type="button" class="skin-field__shop" data-open="shop-dialog">
                            <?php echo gem_icon('skin-field__gem'); ?>
                            <span>Plus d'habillages</span>
                        </button>
                    </div>
                </fieldset>
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
                        <small><?php echo $isMine ? 'Visible par vous seul et les amis choisis ci-dessous, même avec le lien' : 'Visible seulement par ses gestionnaires et les amis choisis ci-dessous, même avec le lien'; ?></small>
                    </span>
                </label>
                <?php if (viewers_enabled()) : ?>
                    <?php
                    // Amis autorisés : affichés seulement quand la case « Liste privée » est cochée (css/app.css).
                    $candidates = list_viewer_candidates($owner);
                    $viewerIds = list_viewer_ids($owner['id']);
                    ?>
                    <fieldset class="field private-viewers" data-private-viewers>
                        <input type="hidden" name="viewers_sent" value="1">
                        <legend>Qui peut la voir quand même ? <small>(<?php echo $isMine ? 'parmi vos amis' : 'parmi les amis de la liste'; ?>)</small></legend>
                        <?php if (0 === count($candidates)) : ?>
                            <p class="private-viewers__empty">Aucun ami pour l'instant : <?php echo $isMine ? 'vous seul la voyez' : 'seuls ses gestionnaires la voient'; ?>.</p>
                        <?php else : ?>
                            <div class="private-viewers__list">
                                <?php foreach ($candidates as $friend) : ?>
                                    <label class="private-viewers__option">
                                        <input type="checkbox" name="viewers[]" value="<?php echo (int) $friend['id']; ?>"<?php echo in_array((int) $friend['id'], $viewerIds) ? ' checked' : ''; ?>>
                                        <?php echo avatar($friend, 'private-viewers__avatar'); ?>
                                        <span><?php echo e($friend['nom']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </fieldset>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <footer class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close>Annuler</button>
            <button type="submit" class="btn btn--primary">Enregistrer</button>
        </footer>
    </form>
</dialog>
