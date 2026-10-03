<?php
/* Une carte de badge (vitrine, templates/partials/badges.php). */
$hidden = $badge['secret'] && !$badge['earned'];
$showProgress = !$badge['earned'] && !$hidden && null !== $badge['value'];
$percent = $showProgress ? (int) round(100 * $badge['value'] / max(1, (int) $badge['threshold'])) : 0;
?>
<li class="trophy-tile medal--<?php echo e($badge['tier']); ?><?php echo $badge['earned'] ? ' is-earned' : ' is-locked'; ?><?php echo 'trophy' === $badge['kind'] ? ' is-trophy' : ''; ?>"<?php echo $showProgress ? ' style="--progress: ' . $percent . '%"' : ''; ?>>
    <?php if (isset($tiers[$badge['tier']]) && !$hidden) : ?>
        <span class="trophy-tile__tier"><?php echo e($tiers[$badge['tier']]); ?></span>
    <?php endif; ?>
    <span class="trophy-tile__medal<?php echo $showProgress ? ' has-progress' : ''; ?>" aria-hidden="true">
        <span class="trophy-tile__icon"><?php echo $hidden ? '❔' : e($badge['emoji']); ?></span>
    </span>
    <strong class="trophy-tile__name"><?php echo $hidden ? 'Badge secret' : e($badge['name']); ?></strong>
    <small class="trophy-tile__desc"><?php echo $hidden ? 'À vous de le découvrir…' : e($badge['description']); ?></small>
    <span class="trophy-tile__foot">
        <?php if ($badge['earned']) : ?>
            <span class="trophy-tile__date"><?php echo icon('circle-check'); ?> <?php echo e(date('d/m/Y', strtotime($badge['earned']))); ?></span>
        <?php elseif ($showProgress) : ?>
            <span class="trophy-tile__count"><?php echo (int) $badge['value']; ?> / <?php echo (int) $badge['threshold']; ?></span>
        <?php endif; ?>
        <?php if ($withGems && !$hidden) : ?>
            <span class="trophy-tile__gems<?php echo $badge['earned'] ? '' : ' is-pending'; ?>">+<?php echo badge_gems($badge); ?> <?php echo gem_icon(); ?></span>
        <?php endif; ?>
    </span>
</li>
