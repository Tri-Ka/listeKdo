<?php
$params = $table ? $table['params'] : array();
$section = $sections[$tab];
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?php echo e($section['label']); ?> · Administration · Liste de Kdo</title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <?php echo render('partials/favicons'); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin.css')); ?>">
    <?php if ('analytics' === $tab) : ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/6.1.0/echarts.min.js" crossorigin="anonymous"></script>
    <?php endif; ?>
    <script type="importmap"><?php echo javascript_import_map(); ?></script>
    <script type="module" src="<?php echo e(asset('js/admin.js')); ?>"></script>
</head>

<body data-theme="home" class="admin-page">
    <header class="admin-bar">
        <a class="admin-bar__back" href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>">
            <?php echo icon('arrow-left'); ?> <span>Ma liste</span>
        </a>
        <p class="admin-bar__title"><?php echo icon('shield-halved'); ?> Administration</p>
        <span class="admin-bar__me"><?php echo avatar($me, 'admin-bar__avatar', false); ?> <span><?php echo e($me['nom']); ?></span></span>
    </header>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <p class="admin-sidebar__label">Gestion du site</p>
            <nav class="admin-nav" aria-label="Administration">
                <?php foreach ($sections as $key => $item) : ?>
                    <a href="<?php echo e(admin_url(array(), array('tab' => $key))); ?>"<?php echo $tab === $key ? ' aria-current="page"' : ''; ?>>
                        <?php echo 'gems' === $key ? gem_icon('admin-nav__gem') : icon($item['icon']); ?>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
    <main class="admin" id="admin-content">
        <div class="admin__intro">
            <h1><?php echo e($section['label']); ?></h1>
            <p><?php echo e($section['description']); ?></p>
        </div>

        <?php if ('analytics' === $tab) : ?>
        <ul class="stats">
            <li class="stat stat--users"><span class="stat__icon"><?php echo icon('users'); ?></span><strong><?php echo (int) $stats['users']; ?></strong><span>utilisateurs</span></li>
            <li class="stat stat--children"><span class="stat__icon"><?php echo icon('layer-group'); ?></span><strong><?php echo (int) $stats['children']; ?></strong><span>listes secondaires</span></li>
            <li class="stat stat--ideas"><span class="stat__icon"><?php echo icon('list'); ?></span><strong><?php echo (int) $stats['ideas']; ?></strong><span>idées</span></li>
            <li class="stat stat--gifted"><span class="stat__icon"><?php echo icon('gift'); ?></span><strong><?php echo (int) $stats['gifted']; ?></strong><span>offertes</span></li>
        </ul>

        <?php echo render('partials/admin_insights', array('insights' => $insights)); ?>
        <?php else : ?>

        <?php if ('gems' === $tab) : ?>
            <?php echo render('partials/admin_gems', array('badges' => $badgesAdmin, 'me' => $me)); ?>
        <?php elseif ('badges' === $tab) : ?>
            <?php echo render('partials/admin_badges', array('badges' => $badgesAdmin)); ?>
        <?php else : ?>
        <section class="panel">
            <?php if ($table) : ?>
                <?php echo render('partials/admin_table_head', array('params' => $params, 'caption' => 'Rechercher dans les ' . ('lists' === $tab ? 'listes' : 'utilisateurs'), 'placeholder' => 'Rechercher un nom…')); ?>
            <?php endif; ?>

            <?php if ('settings' === $tab) : ?>
                <div class="panel__body">
                    <?php echo render('partials/admin_settings'); ?>
                </div>
            <?php else : ?>
                <div class="panel__body" data-dt>
                    <?php echo render('partials/admin_table', array('table' => $table, 'me' => $me)); ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        <?php endif; ?>
    </main>
    </div>

    <dialog class="modal modal--small" id="admin-confirm" aria-labelledby="admin-confirm-title">
        <form method="dialog">
            <header class="modal__header">
                <h2 id="admin-confirm-title" data-confirm-title></h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body"><p data-confirm-text></p></div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--ghost" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary" value="ok" data-confirm-ok></button>
            </footer>
        </form>
    </dialog>

    <dialog class="modal modal--small" id="admin-role" aria-labelledby="admin-role-title">
        <form method="post" action="actions/adminRole.php" data-admin-role>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="">
            <header class="modal__header">
                <h2 id="admin-role-title">Modifier le rôle</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body">
                <strong data-role-name></strong>
                <label class="badge-admin__field"><span>Rôle</span>
                    <select name="role" class="dt__select">
                        <?php foreach (roles() as $key => $label) : ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--light" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </footer>
        </form>
    </dialog>

    <dialog class="modal modal--small" id="admin-password" aria-labelledby="admin-password-title">
        <header class="modal__header">
            <h2 id="admin-password-title">Nouveau mot de passe</h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <p>Transmettez ce mot de passe à <strong data-password-name></strong>. Il ne sera plus affiché ensuite.</p>
            <div class="password-box">
                <code data-password-value></code>
                <button type="button" class="btn btn--soft btn--sm" data-password-copy><?php echo icon('link'); ?> Copier</button>
            </div>
            <p class="admin__hint">Son ancien mot de passe ne fonctionne plus. Il pourra en choisir un nouveau dans « Mon profil ».</p>
        </div>
        <footer class="modal__footer">
            <button type="button" class="btn btn--primary" data-close>C'est noté</button>
        </footer>
    </dialog>

    <dialog class="modal modal--small" id="admin-link" aria-labelledby="admin-link-title">
        <header class="modal__header">
            <h2 id="admin-link-title">Lien de secours</h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <p>Envoyez ce lien à <strong data-link-name></strong> : il lui permet de choisir un nouveau mot de passe, puis le connecte.</p>
            <div class="link-box">
                <input type="text" readonly data-link-value aria-label="Lien de secours">
                <div class="link-box__actions">
                    <button type="button" class="btn btn--primary btn--sm" data-link-copy><?php echo icon('link'); ?> Copier</button>
                    <a class="btn btn--light btn--sm" href="#" target="_blank" rel="noopener" data-link-whatsapp><?php echo icon('whatsapp'); ?> WhatsApp</a>
                    <button type="button" class="btn btn--light btn--sm" data-link-share hidden><?php echo icon('share-nodes'); ?> Partager</button>
                </div>
            </div>
            <p class="admin__hint">Valable une seule fois, jusqu'au <span data-link-expires></span>. Créer un nouveau lien annule celui-ci.</p>
        </div>
        <footer class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close>Fermer</button>
        </footer>
    </dialog>

    <dialog class="modal modal--small" id="admin-managers" aria-labelledby="admin-managers-title">
        <header class="modal__header">
            <h2 id="admin-managers-title">Gestionnaires de <span data-managers-name></span></h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <p class="admin__hint" data-managers-state></p>
            <ul class="managers" data-managers-list></ul>
            <form class="managers__add" data-managers-add>
                <label class="sr-only" for="admin-parent-input">Ajouter un gestionnaire</label>
                <input type="text" id="admin-parent-input" list="admin-parent-options" placeholder="Ajouter un gestionnaire…" autocomplete="off" required>
                <datalist id="admin-parent-options">
                    <?php foreach ($parentOptions as $option) : ?>
                        <option value="<?php echo e($option['nom']); ?>" data-id="<?php echo (int) $option['id']; ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <button type="submit" class="btn btn--primary btn--sm"><?php echo icon('plus'); ?> Ajouter</button>
            </form>
            <p class="admin__hint">Une liste avec au moins un gestionnaire est une liste secondaire. Les gestionnaires peuvent la modifier et voient qui offre quoi ; ils sont aussi ajoutés à ses amis.</p>
        </div>
        <footer class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close>Fermer</button>
        </footer>
    </dialog>

    <div class="toasts" data-toasts aria-live="polite">
        <?php if ($flash) : ?>
            <div class="toast toast--<?php echo e($flash['type']); ?>" role="status"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
