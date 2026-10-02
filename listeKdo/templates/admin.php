<?php
$params = $table['params'];
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Administration · Liste de Kdo</title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin.css')); ?>">
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

    <main class="admin">
        <div class="admin__intro">
            <h1>Tableau de bord</h1>
            <p>Gérez les comptes, les rôles et les listes de Liste de Kdo.</p>
        </div>

        <ul class="stats">
            <li class="stat stat--users"><span class="stat__icon"><?php echo icon('users'); ?></span><strong><?php echo (int) $stats['users']; ?></strong><span>utilisateurs</span></li>
            <li class="stat stat--children"><span class="stat__icon"><?php echo icon('child-reaching'); ?></span><strong><?php echo (int) $stats['children']; ?></strong><span>listes d'enfants</span></li>
            <li class="stat stat--ideas"><span class="stat__icon"><?php echo icon('list'); ?></span><strong><?php echo (int) $stats['ideas']; ?></strong><span>idées</span></li>
            <li class="stat stat--gifted"><span class="stat__icon"><?php echo icon('gift'); ?></span><strong><?php echo (int) $stats['gifted']; ?></strong><span>offertes</span></li>
        </ul>

        <section class="panel">
            <div class="panel__head">
                <nav class="panel__tabs" aria-label="Tableaux">
                    <a href="<?php echo e(admin_url(array(), array('tab' => 'users'))); ?>"<?php echo 'users' === $params['tab'] ? ' aria-current="page"' : ''; ?>><?php echo icon('users'); ?> Utilisateurs</a>
                    <a href="<?php echo e(admin_url(array(), array('tab' => 'lists'))); ?>"<?php echo 'lists' === $params['tab'] ? ' aria-current="page"' : ''; ?>><?php echo icon('list'); ?> Listes</a>
                </nav>

                <form class="search" method="get" action="admin.php" role="search" data-dt-search>
                    <?php foreach (array('tab', 'sort', 'dir', 'filter', 'per') as $key) : ?>
                        <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($params[$key]); ?>">
                    <?php endforeach; ?>
                    <?php echo icon('magnifying-glass', 'search__icon'); ?>
                    <input type="search" name="q" value="<?php echo e($params['q']); ?>" placeholder="Rechercher un nom…" aria-label="Rechercher" autocomplete="off">
                </form>
            </div>

            <div class="panel__body" data-dt>
                <?php echo render('partials/admin_table', array('table' => $table, 'me' => $me)); ?>
            </div>
        </section>
    </main>

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
            <h2 id="admin-managers-title">Parents de <span data-managers-name></span></h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <p class="admin__hint" data-managers-state></p>
            <ul class="managers" data-managers-list></ul>
            <form class="managers__add" data-managers-add>
                <label class="sr-only" for="admin-parent-input">Ajouter un parent</label>
                <input type="text" id="admin-parent-input" list="admin-parent-options" placeholder="Ajouter un parent…" autocomplete="off" required>
                <datalist id="admin-parent-options">
                    <?php foreach ($parentOptions as $option) : ?>
                        <option value="<?php echo e($option['nom']); ?>" data-id="<?php echo (int) $option['id']; ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <button type="submit" class="btn btn--primary btn--sm"><?php echo icon('plus'); ?> Ajouter</button>
            </form>
            <p class="admin__hint">Les parents peuvent modifier la liste et voient qui offre quoi. Le parent est aussi ajouté aux amis de la liste.</p>
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
