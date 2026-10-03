<?php
$pageTitle = $owner ? theme_text($theme, 'heading', $owner) : $theme['title'];
if ($owner && !$ctx['canView']) {
    $pageTitle = 'Liste privée';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageTitle); ?>">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="<?php echo e(!empty($theme['colors']) ? $theme['colors']['hero-from'] : $theme['color']); ?>">
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="http://datcharrye.free.fr/listeKdo/img/<?php echo $owner ? e($theme['key']) . '/' : ''; ?>metaOg.jpg">
    <?php if ($owner) : ?>
        <meta property="og:url" content="<?php echo e(share_url($owner)); ?>">
    <?php endif; ?>
    <link rel="icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Caveat:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <script type="module" src="<?php echo e(asset('js/app.js')); ?>"></script>
</head>

<body data-theme="<?php echo $owner ? e($theme['key']) : 'home'; ?>"<?php echo $owner && $theme['skin'] ? ' data-skin="' . e($theme['skin']) . '" style="' . e(skin_style($theme)) . '"' : ''; ?>>
    <?php echo render('partials/topbar', array('ctx' => $ctx, 'friends' => $friends, 'newNotifications' => $newNotifications, 'theme' => $theme, 'myGifts' => $myGifts, 'shop' => $shop)); ?>

    <?php if ($me) : ?>
        <?php echo render('partials/notifications', array('notifications' => $notifications, 'newNotifications' => $newNotifications)); ?>
    <?php endif; ?>

    <main class="page">
        <?php if (!$owner) : ?>
            <?php echo render('partials/home', array('notFound' => '' !== input('user'))); ?>
        <?php elseif (!$ctx['canView']) : ?>
            <section class="private-notice">
                <span class="private-notice__icon"><?php echo icon('lock'); ?></span>
                <h1>Cette liste est privée</h1>
                <p>Pour l'instant, seule la personne qui l'a créée peut la voir.</p>
                <a class="btn btn--primary" href="index.php"><?php echo icon('gift'); ?> <?php echo $me ? 'Revenir à ma liste' : "Retour à l'accueil"; ?></a>
            </section>
        <?php else : ?>
            <?php echo render('partials/hero', array('ctx' => $ctx, 'theme' => $theme, 'objectCount' => count($objects), 'badges' => $badges)); ?>
            <?php echo render('partials/tabs', array('ctx' => $ctx, 'objects' => $objects)); ?>

            <section class="grid" aria-label="Idées cadeaux" data-grid>
                <?php if ($ctx['canEdit']) : ?>
                    <button type="button" class="card card--add" data-open-object-form>
                        <span class="card--add__icon"><?php echo icon('plus'); ?></span>
                        <strong>Ajouter une idée</strong>
                        <span>Collez un lien, on s'occupe du reste</span>
                    </button>
                <?php endif; ?>

                <?php foreach ($objects as $object) : ?>
                    <?php echo render('partials/card', array('object' => $object, 'ctx' => $ctx)); ?>
                <?php endforeach; ?>
            </section>

            <p class="empty" data-empty<?php echo 0 === count($objects) && !$ctx['canEdit'] ? '' : ' hidden'; ?>>Aucune idée cadeau ici pour l'instant.</p>

            <?php foreach ($objects as $object) : ?>
                <?php echo render('partials/object_dialog', array('object' => $object, 'ctx' => $ctx)); ?>
            <?php endforeach; ?>

            <?php if (!$me) : ?>
                <div class="cta">
                    <button type="button" class="btn btn--primary btn--lg" data-open="signup-dialog"><?php echo icon('gift'); ?> Créer ma liste</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php if ($owner && $ctx['canView']) : ?>
        <?php echo render('partials/footer', array('theme' => $theme, 'owner' => $owner, 'ctx' => $ctx)); ?>
    <?php endif; ?>

    <?php echo render('partials/dialogs', array('ctx' => $ctx)); ?>
    <?php echo render('partials/confirm'); ?>
    <?php echo render('partials/badges', array('ctx' => $ctx, 'owner' => $owner, 'badges' => $badges, 'shop' => $shop)); ?>
    <?php if ($shop) : ?>
        <?php echo render('partials/shop', array('me' => $me, 'shop' => $shop)); ?>
    <?php endif; ?>
    <?php if ($owner && $ctx['canEdit']) : ?>
        <?php echo render('partials/list_settings', array('owner' => $owner, 'ctx' => $ctx, 'shop' => $shop)); ?>
    <?php endif; ?>

    <?php if ($me) : ?>
        <?php echo render('partials/my_gifts', array('myGifts' => $myGifts)); ?>
    <?php endif; ?>

    <div class="toasts" data-toasts aria-live="polite">
        <?php if ($flash) : ?>
            <div class="toast toast--<?php echo e($flash['type']); ?>" role="status"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
