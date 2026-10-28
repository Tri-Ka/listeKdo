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

<section class="admin-settings">
    <h2>GIF dans les commentaires</h2>
    <p class="admin__hint">Avec une clé GIPHY, un bouton « GIF » permet de chercher et d’envoyer un GIF dans les commentaires. Créez une clé gratuite sur <a href="https://developers.giphy.com/dashboard/" target="_blank" rel="noopener">developers.giphy.com</a> (« Create an API Key », type <strong>API</strong>). Sans clé, le bouton n’apparaît pas.</p>
    <form method="post" action="actions/adminGiphy.php" data-giphy-settings>
        <?php echo csrf_field(); ?>
        <label class="field">
            <span>Clé d’API GIPHY</span>
            <input type="text" name="key" value="<?php echo e(giphy_key()); ?>" autocomplete="off" spellcheck="false" maxlength="64" placeholder="Laisser vide pour désactiver">
        </label>
        <p class="admin__hint">La recherche part du navigateur des visiteurs connectés : la clé leur est donc visible, comme pour toute intégration GIPHY. Une clé de test est limitée à 100 recherches par heure.</p>
        <div class="admin-settings__actions">
            <button type="submit" class="btn btn--primary"<?php echo !settings_enabled() ? ' disabled' : ''; ?>>Enregistrer</button>
            <button type="button" class="btn btn--light" data-giphy-test>Tester</button>
        </div>
        <p class="admin__hint" data-giphy-status role="status" aria-live="polite"></p>
    </form>
</section>
