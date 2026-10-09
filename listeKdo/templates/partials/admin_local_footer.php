<?php /* Pagination locale avec les mêmes composants que les tableaux Utilisateurs et Listes. */ ?>
<footer class="dt__footer" data-<?php echo e($prefix); ?>-controls hidden>
    <label class="dt__per">Lignes par page
        <select data-<?php echo e($prefix); ?>-per>
            <?php foreach (array(10, 25, 50, 100) as $per) : ?><option value="<?php echo (int) $per; ?>"><?php echo (int) $per; ?></option><?php endforeach; ?>
        </select>
    </label>
    <nav class="dt__pages" data-<?php echo e($prefix); ?>-pagination aria-label="<?php echo e($paginationLabel); ?>">
        <button type="button" class="dt__page" data-<?php echo e($prefix); ?>-prev aria-label="Page précédente"><?php echo icon('chevron-left'); ?></button>
        <span class="dt__pages" data-<?php echo e($prefix); ?>-pages></span>
        <button type="button" class="dt__page" data-<?php echo e($prefix); ?>-next aria-label="Page suivante"><?php echo icon('chevron-right'); ?></button>
        <span class="sr-only" data-<?php echo e($prefix); ?>-page></span>
    </nav>
</footer>
