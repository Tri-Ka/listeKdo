<?php $themes = themes(); ?>
<section class="home-hero">
    <div class="home-hero__text">
        <?php if ($notFound) : ?>
            <p class="home-hero__alert"><?php echo icon('triangle-exclamation'); ?> Cette liste n'existe pas ou plus.</p>
        <?php endif; ?>
        <h1>Une liste de cadeaux à partager avec vos proches</h1>
        <p>Anniversaire, Noël, naissance : ajoutez vos idées, envoyez le lien, chacun réserve ce qu'il offre.</p>
        <div class="home-hero__actions">
            <button type="button" class="btn btn--primary btn--lg" data-open="signup-dialog"><?php echo icon('gift'); ?> Créer ma liste</button>
            <button type="button" class="btn btn--light btn--lg" data-open="login-dialog">J'ai déjà un compte</button>
        </div>
    </div>

    <div class="occasions" aria-label="Thèmes disponibles">
        <?php foreach (array('birthday', 'noel', 'naissance') as $key) : ?>
            <div class="occasion occasion--<?php echo e($key); ?>">
                <img src="<?php echo e(asset('img/deco/' . $key . '/title.png')); ?>" alt="<?php echo e($themes[$key]['title']); ?>" width="300" height="140">
                <span><?php echo e($themes[$key]['label']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="steps" aria-label="Comment ça marche">
    <div class="step">
        <span class="step__icon"><?php echo icon('link'); ?></span>
        <h2>Ajoutez vos idées</h2>
        <p>Collez le lien d'un produit : le nom et la photo se remplissent tout seuls.</p>
    </div>
    <div class="step">
        <span class="step__icon"><?php echo icon('share-nodes'); ?></span>
        <h2>Partagez le lien</h2>
        <p>Par WhatsApp, mail ou SMS. Pas besoin de compte pour consulter la liste.</p>
    </div>
    <div class="step">
        <span class="step__icon"><?php echo icon('gift'); ?></span>
        <h2>Pas de doublon</h2>
        <p>Chacun voit ce qui est déjà réservé. Vous, vous ne voyez rien : la surprise reste entière.</p>
    </div>
</section>

<section class="extension-promo">
    <span class="extension-promo__icon"><?php echo icon('chrome'); ?></span>
    <div>
        <h2>Ajoutez depuis n'importe quelle boutique</h2>
        <p>Avec l'extension Chrome, un clic sur la page d'un produit suffit pour l'ajouter à votre liste.</p>
    </div>
    <button type="button" class="btn btn--light" data-open="extension-dialog"><?php echo icon('download'); ?> L'extension</button>
</section>
