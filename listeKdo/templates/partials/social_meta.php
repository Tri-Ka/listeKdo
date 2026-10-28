<meta name="description" content="<?php echo e($meta['description']); ?>">
<link rel="canonical" href="<?php echo e($meta['url']); ?>">
<?php if ($meta['noIndex']) : ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<meta property="og:site_name" content="ListeKdo">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo e($meta['title']); ?>">
<meta property="og:description" content="<?php echo e($meta['description']); ?>">
<meta property="og:url" content="<?php echo e($meta['url']); ?>">
<meta property="og:image" content="<?php echo e($meta['image']); ?>">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?php echo e($meta['imageAlt']); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo e($meta['title']); ?>">
<meta name="twitter:description" content="<?php echo e($meta['description']); ?>">
<meta name="twitter:image" content="<?php echo e($meta['image']); ?>">
<meta name="twitter:image:alt" content="<?php echo e($meta['imageAlt']); ?>">
