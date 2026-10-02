<?php
/*
 * Choix du type de liste (thème) : inscription, « Mon profil », listes d'enfants.
 * $current : thème coché.
 */
$current = isset($current) ? (string) $current : 'noel';
?>
<fieldset class="field theme-field">
    <legend>Type de liste</legend>
    <div class="theme-field__options">
        <?php foreach (themes() as $key => $info) : ?>
            <label class="theme-field__option theme-field__option--<?php echo e($key); ?>">
                <input type="radio" name="theme" value="<?php echo e($key); ?>"<?php echo $key === $current ? ' checked' : ''; ?>>
                <img src="<?php echo e(asset('img/deco/' . $key . '/' . $info['footer'] . '.png')); ?>" alt="" width="64" height="64" decoding="async">
                <span><?php echo e($info['label']); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
</fieldset>
