<?php
/*
 * Administration › Badges : un formulaire par badge (actions/adminBadge.php), puis l'ajout d'un badge.
 * Un badge désactivé disparaît des vitrines, mais ceux qui l'ont obtenu le gardent s'il est réactivé.
 */
$metrics = badge_metrics();
$tiers = badge_tiers();
$kinds = badge_kinds();
$blank = array('id' => 0, 'code' => '', 'kind' => 'badge', 'name' => '', 'description' => '', 'emoji' => '🏅', 'tier' => 'bronze',
    'metric' => 'ideas', 'threshold' => 1, 'gems' => 0, 'secret' => 0, 'active' => 1, 'position' => (count($badges) + 1) * 10, 'holders' => 0);
?>
<div class="dt__bar">
    <p class="dt__count">
        <strong><?php echo count($badges); ?></strong> badges et trophées.
        Un badge s'obtient quand l'indicateur atteint le seuil (vérifié à chaque visite).
    </p>
    <form method="post" action="actions/adminBadge.php">
        <?php echo csrf_field(); ?>
        <button type="submit" name="op" value="install" class="btn btn--light btn--sm" title="Ajoute les badges par défaut qui manquent (sans toucher aux autres)">Badges par défaut manquants</button>
    </form>
</div>

<div class="badge-admin">
    <?php foreach (array_merge($badges, array($blank)) as $badge) : ?>
        <?php $isNew = 0 === (int) $badge['id']; ?>
        <form class="badge-admin__row<?php echo $isNew ? ' badge-admin__row--new' : ''; ?><?php echo !$badge['active'] ? ' is-inactive' : ''; ?>" method="post" action="actions/adminBadge.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $badge['id']; ?>">
            <?php if ($isNew) : ?>
                <p class="badge-admin__new-title">Nouveau badge</p>
            <?php endif; ?>
            <label class="badge-admin__emoji medal--<?php echo e($badge['tier']); ?>">
                <span class="sr-only">Emoji</span>
                <input type="text" name="emoji" value="<?php echo e($badge['emoji']); ?>" maxlength="8" required>
            </label>
            <label class="badge-admin__field badge-admin__field--name">
                <span>Nom</span>
                <input type="text" name="name" value="<?php echo e($badge['name']); ?>" maxlength="80" required>
            </label>
            <label class="badge-admin__field badge-admin__field--desc">
                <span>Description</span>
                <input type="text" name="description" value="<?php echo e($badge['description']); ?>" maxlength="255">
            </label>
            <label class="badge-admin__field">
                <span>Indicateur</span>
                <select name="metric" class="dt__select">
                    <?php foreach ($metrics as $key => $label) : ?>
                        <option value="<?php echo e($key); ?>"<?php echo $key === $badge['metric'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="badge-admin__field badge-admin__field--small">
                <span>Seuil</span>
                <input type="number" name="threshold" value="<?php echo (int) $badge['threshold']; ?>" min="1" max="100000" required>
            </label>
            <label class="badge-admin__field badge-admin__field--small">
                <span>Type</span>
                <select name="kind" class="dt__select">
                    <?php foreach ($kinds as $key => $label) : ?>
                        <option value="<?php echo e($key); ?>"<?php echo $key === $badge['kind'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="badge-admin__field badge-admin__field--small">
                <span>Niveau</span>
                <select name="tier" class="dt__select">
                    <?php foreach ($tiers as $key => $label) : ?>
                        <option value="<?php echo e($key); ?>"<?php echo $key === $badge['tier'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if (skins_enabled()) : ?>
                <label class="badge-admin__field badge-admin__field--small" title="0 = automatique (<?php echo badge_default_gems($badge['kind'], $badge['tier']); ?> pour ce type et ce niveau)">
                    <span><?php echo gem_icon(); ?> Gemmes</span>
                    <input type="number" name="gems" value="<?php echo isset($badge['gems']) ? (int) $badge['gems'] : 0; ?>" min="0" max="10000" placeholder="<?php echo badge_default_gems($badge['kind'], $badge['tier']); ?>">
                </label>
            <?php endif; ?>
            <label class="badge-admin__field badge-admin__field--small">
                <span>Ordre</span>
                <input type="number" name="position" value="<?php echo (int) $badge['position']; ?>">
            </label>
            <div class="badge-admin__checks">
                <label><input type="checkbox" name="active" value="1"<?php echo $badge['active'] ? ' checked' : ''; ?>> Actif</label>
                <label title="Caché (« ??? ») tant qu'il n'est pas obtenu"><input type="checkbox" name="secret" value="1"<?php echo $badge['secret'] ? ' checked' : ''; ?>> Secret</label>
            </div>
            <div class="badge-admin__actions">
                <?php if (!$isNew) : ?>
                    <span class="badge-admin__holders" title="Personnes qui l'ont obtenu"><?php echo (int) $badge['holders']; ?> <?php echo icon('users'); ?></span>
                <?php endif; ?>
                <button type="submit" name="op" value="save" class="btn btn--primary btn--sm"><?php echo $isNew ? 'Ajouter' : 'Enregistrer'; ?></button>
            </div>
        </form>
    <?php endforeach; ?>
</div>
