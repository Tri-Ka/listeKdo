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

<?php if (skins_enabled()) : ?>
    <?php $rates = gem_action_rates(); ?>
    <form class="gem-rates" method="post" action="actions/adminGems.php">
        <?php echo csrf_field(); ?>
        <p class="gem-rates__title"><?php echo gem_icon(); ?> Gemmes par action <small>(en plus des badges ; calculées sur ce qui existe, donc rétroactives)</small></p>
        <div class="gem-rates__fields">
            <?php foreach (gem_actions() as $key => $info) : ?>
                <label class="badge-admin__field">
                    <span><?php echo e($info[0]); ?></span>
                    <input type="number" name="rate[<?php echo e($key); ?>]" value="<?php echo (int) $rates[$key]; ?>" min="0" max="1000" title="Par défaut : <?php echo (int) $info[1]; ?>">
                </label>
            <?php endforeach; ?>
            <?php if (referral_enabled()) : ?>
                <?php $rewards = referral_rewards(); ?>
                <label class="badge-admin__field">
                    <span>Parrain (filleul actif)</span>
                    <input type="number" name="referral[sponsor]" value="<?php echo (int) $rewards['sponsor']; ?>" min="0" max="100000" title="Par défaut : 200">
                </label>
                <label class="badge-admin__field">
                    <span>Bienvenue (filleul)</span>
                    <input type="number" name="referral[welcome]" value="<?php echo (int) $rewards['welcome']; ?>" min="0" max="100000" title="Par défaut : 50">
                </label>
            <?php endif; ?>
            <?php if (settings_enabled()) : ?>
                <button type="submit" class="btn btn--primary btn--sm">Enregistrer</button>
            <?php endif; ?>
        </div>
        <?php if (!settings_enabled()) : ?>
            <p class="gem-rates__warning"><?php echo icon('triangle-exclamation'); ?> Valeurs par défaut en place : pour pouvoir les modifier, exécutez <code>sql/2026-10-03-gemmes-actions.sql</code> dans phpMyAdmin.</p>
        <?php endif; ?>
    </form>
<?php endif; ?>

<?php if (skins_enabled()) : ?>
    <form class="gem-rates" method="post" action="actions/adminGems.php">
        <?php echo csrf_field(); ?>
        <p class="gem-rates__title"><?php echo gem_icon(); ?> Prix de la boutique <small>(en gemmes ; un habillage, pour chaque type de liste ; les achats déjà faits ne changent pas)</small></p>
        <div class="gem-rates__fields">
            <?php $rarities = skin_rarities(); ?>
            <?php foreach (skins() as $key => $skin) : ?>
                <label class="badge-admin__field">
                    <span><?php echo e($skin['label']); ?> · <?php echo e($rarities[$skin['rarity']]); ?></span>
                    <input type="number" name="price[<?php echo e($key); ?>]" value="<?php echo skin_price($key, $skin['price']); ?>" min="1" max="100000" title="Par défaut : <?php echo (int) $skin['price']; ?>">
                </label>
            <?php endforeach; ?>
            <?php if (accessories_enabled()) : ?>
                <?php // Cadres et effets de compte à rebours : un prix par article (réglage « price_frame_ruban »…), par défaut selon la rareté. ?>
                <?php $accessoryKinds = accessory_kinds(); ?>
                <?php foreach (accessory_items() as $id => $item) : ?>
                    <label class="badge-admin__field">
                        <span><?php echo e($accessoryKinds[$item['kind']]['label']); ?> · <?php echo e($item['label']); ?> · <?php echo e($rarities[$item['rarity']]); ?></span>
                        <input type="number" name="price[<?php echo e(accessory_price_setting($id)); ?>]" value="<?php echo (int) $item['price']; ?>" min="1" max="100000" title="Par défaut : <?php echo (int) $item['default_price']; ?>">
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (settings_enabled()) : ?>
                <button type="submit" class="btn btn--primary btn--sm">Enregistrer</button>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php
// Une ligne compacte par badge ; « Modifier » déplie son formulaire (un seul formulaire visible à la fois en pratique).
$rows = $badges;
$rows[] = $blank;
?>
<ul class="badge-admin">
    <?php foreach ($rows as $badge) : ?>
        <?php $isNew = 0 === (int) $badge['id']; ?>
        <li>
            <details class="badge-admin__row<?php echo $isNew ? ' badge-admin__row--new' : ''; ?><?php echo !$badge['active'] ? ' is-inactive' : ''; ?>">
                <summary class="badge-admin__summary">
                    <?php if ($isNew) : ?>
                        <span class="badge-admin__medal badge-admin__medal--add"><?php echo icon('plus'); ?></span>
                        <span class="badge-admin__info"><strong>Ajouter un badge</strong><small>Choisissez un indicateur et un seuil : il sera attribué automatiquement.</small></span>
                    <?php else : ?>
                        <span class="badge-admin__medal medal--<?php echo e($badge['tier']); ?>"><?php echo e($badge['emoji']); ?></span>
                        <span class="badge-admin__info">
                            <strong><?php echo e($badge['name']); ?></strong>
                            <small><?php echo e(isset($metrics[$badge['metric']]) ? $metrics[$badge['metric']] : $badge['metric']); ?> ≥ <?php echo (int) $badge['threshold']; ?></small>
                        </span>
                        <span class="badge-admin__chips">
                            <span class="badge-admin__chip medal--<?php echo e($badge['tier']); ?>"><?php echo 'trophy' === $badge['kind'] ? '🏆 ' : ''; ?><?php echo e($tiers[$badge['tier']]); ?></span>
                            <?php if (skins_enabled()) : ?>
                                <span class="badge-admin__chip badge-admin__chip--gems">+<?php echo badge_gems($badge); ?> <?php echo gem_icon(); ?></span>
                            <?php endif; ?>
                            <?php if ($badge['secret']) : ?><span class="badge-admin__chip">Secret</span><?php endif; ?>
                            <?php if (!$badge['active']) : ?><span class="badge-admin__chip badge-admin__chip--off">Désactivé</span><?php endif; ?>
                        </span>
                        <span class="badge-admin__holders" title="Personnes qui l'ont obtenu"><?php echo (int) $badge['holders']; ?> <?php echo icon('users'); ?></span>
                    <?php endif; ?>
                    <span class="badge-admin__toggle"><?php echo $isNew ? 'Créer' : 'Modifier'; ?></span>
                </summary>

                <form class="badge-admin__form" method="post" action="actions/adminBadge.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $badge['id']; ?>">
                    <label class="badge-admin__field badge-admin__field--emoji">
                        <span>Emoji</span>
                        <input type="text" name="emoji" value="<?php echo e($badge['emoji']); ?>" maxlength="8" required>
                    </label>
                    <label class="badge-admin__field badge-admin__field--wide">
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
                    <?php if (skins_enabled()) : ?>
                        <label class="badge-admin__field" title="Valeur par défaut pour ce type et ce niveau : <?php echo badge_default_gems($badge['kind'], $badge['tier']); ?>">
                            <span>Gemmes</span>
                            <input type="number" name="gems" value="<?php echo badge_gems($badge); ?>" min="1" max="10000">
                        </label>
                    <?php endif; ?>
                    <label class="badge-admin__field">
                        <span>Ordre</span>
                        <input type="number" name="position" value="<?php echo (int) $badge['position']; ?>">
                    </label>
                    <div class="badge-admin__checks">
                        <label><input type="checkbox" name="active" value="1"<?php echo $badge['active'] ? ' checked' : ''; ?>> Actif</label>
                        <label title="Caché (« ??? ») tant qu'il n'est pas obtenu"><input type="checkbox" name="secret" value="1"<?php echo $badge['secret'] ? ' checked' : ''; ?>> Secret</label>
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm badge-admin__save"><?php echo $isNew ? 'Ajouter le badge' : 'Enregistrer'; ?></button>
                </form>
            </details>
        </li>
    <?php endforeach; ?>
</ul>
