<?php
$isNew = 0 === (int) $badge['id'];
$metrics = badge_metrics();
$tiers = badge_tiers();
$kinds = badge_kinds();
?>
<form class="badge-admin__form" method="post" action="actions/adminBadge.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int) $badge['id']; ?>">
    <label class="badge-admin__field badge-admin__field--emoji">
        <span>Emoji</span>
        <input type="text" name="emoji" value="<?php echo e($badge['emoji']); ?>" maxlength="8" required>
    </label>
    <label class="badge-admin__field badge-admin__field--wide badge-admin__field--name">
        <span>Nom</span>
        <input type="text" name="name" value="<?php echo e($badge['name']); ?>" maxlength="80" required>
    </label>
    <label class="badge-admin__field badge-admin__field--full">
        <span>Description</span>
        <input type="text" name="description" value="<?php echo e($badge['description']); ?>" maxlength="255">
    </label>
    <label class="badge-admin__field badge-admin__field--wide">
        <span>Indicateur</span>
        <select name="metric" class="dt__select">
            <?php foreach ($metrics as $key => $label) : ?>
                <option value="<?php echo e($key); ?>"<?php echo $key === $badge['metric'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="badge-admin__field">
        <span>Seuil</span>
        <input type="number" name="threshold" value="<?php echo (int) $badge['threshold']; ?>" min="1" max="100000" required>
    </label>
    <label class="badge-admin__field">
        <span>Type</span>
        <select name="kind" class="dt__select">
            <?php foreach ($kinds as $key => $label) : ?>
                <option value="<?php echo e($key); ?>"<?php echo $key === $badge['kind'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="badge-admin__field">
        <span>Niveau</span>
        <select name="tier" class="dt__select">
            <?php foreach ($tiers as $key => $label) : ?>
                <option value="<?php echo e($key); ?>"<?php echo $key === $badge['tier'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="badge-admin__field">
        <span>Ordre</span>
        <input type="number" name="position" value="<?php echo (int) $badge['position']; ?>">
    </label>
    <?php if (skins_enabled()) : ?>
        <label class="badge-admin__field badge-admin__field--wide">
            <span>Gemmes offertes</span>
            <input type="number" name="gems" value="<?php echo (int) $badge['gems']; ?>" min="0" max="10000" required>
            <small>0 = automatique selon le type et le niveau. Le montant s’applique aussi aux badges déjà obtenus.</small>
        </label>
    <?php endif; ?>
    <div class="badge-admin__checks">
        <label><input type="checkbox" name="active" value="1"<?php echo $badge['active'] ? ' checked' : ''; ?>> Actif</label>
        <label title="Caché (« ??? ») tant qu'il n'est pas obtenu"><input type="checkbox" name="secret" value="1"<?php echo $badge['secret'] ? ' checked' : ''; ?>> Secret</label>
    </div>
    <div class="badge-admin__footer">
    <button type="button" class="btn btn--light btn--sm" data-close>Annuler</button>
    <button type="submit" class="btn btn--primary btn--sm badge-admin__save"><?php echo $isNew ? 'Ajouter le badge' : 'Enregistrer'; ?></button>
    </div>
</form>
