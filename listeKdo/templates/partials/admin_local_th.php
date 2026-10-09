<th scope="col"<?php echo !empty($numeric) ? ' class="dt__num"' : ''; ?> aria-sort="none">
    <button type="button" class="dt__sort" data-<?php echo e($prefix); ?>-sort="<?php echo e($column); ?>">
        <?php echo e($label); ?><span data-<?php echo e($prefix); ?>-sort-icon aria-hidden="true"><?php echo icon('sort'); ?></span>
    </button>
</th>
