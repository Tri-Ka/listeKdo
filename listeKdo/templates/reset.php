<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="no-referrer">
    <title>Nouveau mot de passe · Liste de Kdo</title>
    <link rel="icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin.css')); ?>">
</head>

<body data-theme="home" class="admin-page reset-page">
    <main class="reset-card">
        <span class="reset-card__icon"><?php echo icon($user ? 'key' : 'triangle-exclamation'); ?></span>

        <?php if ($flash) : ?>
            <p class="reset-card__flash reset-card__flash--<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></p>
        <?php endif; ?>

        <?php if ($user) : ?>
            <h1>Bonjour <?php echo e($user['nom']); ?> !</h1>
            <p>Choisissez votre nouveau mot de passe. Ce lien ne servira qu'une fois.</p>

            <form method="post" action="actions/resetByLink.php" class="reset-card__form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="t" value="<?php echo e($param); ?>">
                <label>
                    <span>Nouveau mot de passe</span>
                    <input type="password" name="password" required minlength="4" autocomplete="new-password" autofocus>
                </label>
                <label>
                    <span>Confirmation</span>
                    <input type="password" name="re-password" required minlength="4" autocomplete="new-password">
                </label>
                <button type="submit" class="btn btn--primary btn--lg">Enregistrer et me connecter</button>
            </form>
        <?php else : ?>
            <h1>Lien expiré</h1>
            <p>Ce lien de secours n'est plus valable : il a déjà servi ou a plus de <?php echo (int) RESET_LINK_HOURS; ?> heures. Demandez-en un nouveau.</p>
            <a class="btn btn--primary" href="index.php">Aller à l'accueil</a>
        <?php endif; ?>
    </main>
</body>
</html>
