<?php
/* En-tête commun : libellé à gauche et recherche à droite, pour tous les tableaux. */
$local = isset($prefix);
?>
<header class="panel__head"<?php echo $local ? ' data-' . e($prefix) . '-controls hidden' : ''; ?>>
    <p class="panel__caption"<?php echo isset($captionId) ? ' id="' . e($captionId) . '"' : ''; ?>><?php echo e($caption); ?></p>
    <?php if ($local) : ?>
        <label class="search">
            <?php echo icon('magnifying-glass', 'search__icon'); ?>
            <input type="search" data-<?php echo e($prefix); ?>-search placeholder="<?php echo e($placeholder); ?>" aria-label="<?php echo e($caption); ?>" autocomplete="off">
        </label>
    <?php else : ?>
        <form class="search" method="get" action="admin.php" role="search" data-dt-search>
            <?php foreach (array('tab', 'sort', 'dir', 'filter', 'per') as $key) : ?>
                <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($params[$key]); ?>">
            <?php endforeach; ?>
            <?php echo icon('magnifying-glass', 'search__icon'); ?>
            <input type="search" name="q" value="<?php echo e($params['q']); ?>" placeholder="<?php echo e($placeholder); ?>" aria-label="Rechercher" autocomplete="off">
        </form>
    <?php endif; ?>
</header>
