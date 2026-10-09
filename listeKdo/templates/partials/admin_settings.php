<?php $provider = short_link_provider(); ?>
<section class="admin-settings">
    <h2>Liens de partage</h2>
    <p class="admin__hint">Choisissez comment raccourcir les liens des listes, de parrainage, d’invitation et de secours.</p>
    <form method="post" action="actions/adminShortLinks.php" data-short-link-settings>
        <?php echo csrf_field(); ?>
        <label class="field">
            <span>Service de liens courts</span>
            <select name="provider">
                <?php foreach (short_link_options() as $key => $label) : ?>
                    <option value="<?php echo e($key); ?>"<?php echo $provider === $key ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="admin__hint">Le mode automatique conserve les liens déjà créés et utilise da.gd pour les nouveaux liens. Avec da.gd, la demande part de votre navigateur : elle fonctionne même si Free bloque les connexions sortantes.</p>
        <p class="admin__hint">TinyURL, is.gd et v.gd nécessitent une connexion sortante du serveur. « Tester » vérifie le service choisi sans modifier votre réglage.</p>
        <div class="admin-settings__actions">
            <button type="submit" name="op" value="save" class="btn btn--primary"<?php echo !settings_enabled() ? ' disabled' : ''; ?>>Enregistrer</button>
            <button type="button" class="btn btn--light" data-short-link-test>Tester</button>
        </div>
        <p class="admin__hint" data-short-link-status role="status" aria-live="polite"></p>
        <?php if (!settings_enabled()) : ?>
            <p class="admin__hint">Le mode automatique est actif. Pour enregistrer un choix, ajoutez la table des réglages avec la migration <code>sql/2026-10-03-gemmes-actions.sql</code>.</p>
        <?php endif; ?>
    </form>
</section>
