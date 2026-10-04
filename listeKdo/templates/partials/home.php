<?php
// Accueil d'un visiteur sans liste (non connecté, ou lien de liste introuvable).
$themes = themes();
$shopOn = skins_enabled();
$rarities = skin_rarities();
// Habillages mis en vitrine : du plus simple au plus rare.
$showcase = array('pastel', 'pirate', 'elegance', 'cosmique', 'neon', 'diamant');
$homeBadges = array(
    array('💡', 'Première idée', 'bronze'),
    array('🎀', 'Premier cadeau', 'bronze'),
    array('🤝', "Esprit d'équipe", 'silver'),
    array('🌟', 'Star', 'gold'),
    array('🧝', 'Lutin en chef', 'legend'),
    array('🎅', 'Père Noël du quartier', 'gold'),
);
?>
<section class="home-hero">
    <div class="home-hero__text">
        <?php if ($notFound) : ?>
            <p class="home-hero__alert"><?php echo icon('triangle-exclamation'); ?> Cette liste n'existe pas ou plus.</p>
        <?php endif; ?>
        <p class="home-eyebrow"><?php echo icon('gift'); ?> Gratuit · sans pub · sans appli</p>
        <h1>La liste de cadeaux qui <em>garde la surprise</em></h1>
        <p class="home-hero__lead">Ajoutez vos idées, envoyez le lien à vos proches : chacun réserve ce qu'il offre, sans doublon. Vous, vous ne voyez rien.</p>
        <div class="home-hero__actions">
            <button type="button" class="btn btn--primary btn--lg" data-open="signup-dialog"><?php echo icon('gift'); ?> Créer ma liste</button>
            <button type="button" class="btn btn--light btn--lg" data-open="login-dialog">J'ai déjà un compte</button>
        </div>
        <ul class="home-checks">
            <li><?php echo icon('check'); ?> Pas de compte pour consulter</li>
            <li><?php echo icon('check'); ?> Ajout depuis n'importe quel site</li>
            <li><?php echo icon('check'); ?> Téléphone et ordinateur</li>
        </ul>
    </div>

    <div class="home-hero__visual" aria-hidden="true">
        <div class="home-phone">
            <img src="<?php echo e(asset('img/guide/mobile.jpg')); ?>" alt="" width="390" height="844" decoding="async">
        </div>
        <div class="home-float home-float--gift"><span>🎁</span><div><strong>Réservé par Paul</strong><small>Le propriétaire ne le voit pas</small></div></div>
        <?php if ($shopOn) : ?>
            <div class="home-float home-float--badge"><span>🏅</span><div><strong>Nouveau badge !</strong><small>Première idée · +5 <?php echo gem_icon(); ?></small></div></div>
        <?php endif; ?>
        <div class="home-float home-float--heart"><?php echo icon('heart'); ?> Coup de cœur</div>
    </div>
</section>

<section class="home-section home-steps" aria-labelledby="home-steps-title">
    <h2 id="home-steps-title" class="home-title">Simple comme 1, 2, 3</h2>
    <ol class="home-steps__list">
        <li>
            <span class="home-steps__icon"><?php echo icon('link'); ?></span>
            <h3>Ajoutez vos idées</h3>
            <p>Collez le lien d'un produit : nom, photo et prix se remplissent tout seuls.</p>
        </li>
        <li>
            <span class="home-steps__icon"><?php echo icon('share-nodes'); ?></span>
            <h3>Partagez le lien</h3>
            <p>Par WhatsApp, mail ou SMS. Vos proches consultent la liste sans créer de compte.</p>
        </li>
        <li>
            <span class="home-steps__icon"><?php echo icon('gift'); ?></span>
            <h3>Ils réservent en secret</h3>
            <p>Chacun voit ce qui est déjà pris. Vous, vous gardez la surprise jusqu'au jour J.</p>
        </li>
    </ol>
</section>

<section class="home-section" aria-labelledby="home-themes-title">
    <h2 id="home-themes-title" class="home-title">Une liste pour chaque occasion</h2>
    <p class="home-subtitle">Chaque type de liste a ses couleurs, ses décorations et son compte à rebours jusqu'au grand jour.</p>
    <div class="occasions" aria-label="Types de liste">
        <?php foreach (array_keys($themes) as $key) : ?>
            <div class="occasion occasion--<?php echo e($key); ?>">
                <img src="<?php echo e(asset('img/deco/' . $key . '/title.png')); ?>" alt="<?php echo e($themes[$key]['title']); ?>" width="300" height="140" loading="lazy" decoding="async">
                <span><?php echo e($themes[$key]['label']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section" aria-labelledby="home-features-title">
    <h2 id="home-features-title" class="home-title">Tout ce qu'il faut, rien de trop</h2>
    <div class="home-features">
        <div class="home-feature">
            <span class="home-feature__icon"><?php echo icon('users'); ?></span>
            <h3>Cadeau à plusieurs</h3>
            <p>Un gros cadeau ? Chacun indique sa participation jusqu'à atteindre le prix.</p>
        </div>
        <div class="home-feature">
            <span class="home-feature__icon"><?php echo icon('layer-group'); ?></span>
            <h3>Collections</h3>
            <p>Les tomes d'une BD, des Lego… chaque élément se réserve séparément.</p>
        </div>
        <div class="home-feature">
            <span class="home-feature__icon"><?php echo icon('heart'); ?></span>
            <h3>Coups de cœur et budget</h3>
            <p>Montrez ce qui vous ferait le plus plaisir ; vos proches filtrent par prix.</p>
        </div>
        <div class="home-feature">
            <span class="home-feature__icon"><?php echo icon('child-reaching'); ?></span>
            <h3>Listes secondaires</h3>
            <p>Gérez aussi la liste de vos enfants, à plusieurs gestionnaires.</p>
        </div>
        <div class="home-feature">
            <span class="home-feature__icon"><?php echo icon('comments'); ?></span>
            <h3>Commentaires et réactions</h3>
            <p>Posez une question sur une idée (taille, couleur…), réagissez d'un emoji.</p>
        </div>
        <div class="home-feature">
            <span class="home-feature__icon"><?php echo icon('lock'); ?></span>
            <h3>Liste privée</h3>
            <p>Gardez-la pour vous, ou montrez-la seulement aux amis que vous choisissez.</p>
        </div>
    </div>
</section>

<?php if ($shopOn) : ?>
    <section class="home-play" aria-labelledby="home-play-title">
        <div class="home-play__head">
            <p class="home-eyebrow home-eyebrow--light"><?php echo gem_icon(); ?> En bonus</p>
            <h2 id="home-play-title">Gagnez des badges, collectez des gemmes, habillez votre liste</h2>
            <p>Chaque action sur le site vous rapproche d'un badge, et chaque badge rapporte des gemmes à dépenser dans la boutique.</p>
        </div>

        <div class="home-play__grid">
            <div class="home-play__card">
                <h3><span class="home-play__num">1</span> Des badges à débloquer</h3>
                <p>Plus de 90 badges et trophées, du bronze au légendaire, et quelques secrets à découvrir.</p>
                <ul class="home-medals">
                    <?php foreach ($homeBadges as $badge) : ?>
                        <li class="home-medal home-medal--<?php echo e($badge[2]); ?>" title="<?php echo e($badge[1]); ?>"><span><?php echo $badge[0]; ?></span><small><?php echo e($badge[1]); ?></small></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="home-play__card">
                <h3><span class="home-play__num">2</span> Des gemmes à chaque geste</h3>
                <p>Vos badges vous en rapportent, vos actions aussi :</p>
                <ul class="home-rates">
                    <?php $actions = gem_actions(); ?>
                    <?php foreach (gem_action_rates() as $key => $rate) : ?>
                        <?php if (0 < $rate) : ?>
                            <li><?php echo e($actions[$key][0]); ?> <b>+<?php echo (int) $rate; ?> <?php echo gem_icon(); ?></b></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (referral_enabled()) : ?>
                        <?php $rewards = referral_rewards(); ?>
                        <?php if (0 < $rewards['sponsor']) : ?>
                            <li>Proche parrainé <b>+<?php echo (int) $rewards['sponsor']; ?> <?php echo gem_icon(); ?></b></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="home-play__card home-play__card--shop">
                <h3><span class="home-play__num">3</span> Une boutique d'habillages</h3>
                <p>Pirate, néon, cosmique… changez le titre, les décorations et les couleurs de votre liste.</p>
                <ul class="home-skins">
                    <?php $allSkins = skins(); ?>
                    <?php foreach ($showcase as $key) : ?>
                        <?php if (isset($allSkins[$key])) : ?>
                            <?php $skin = $allSkins[$key]; ?>
                            <li class="home-skin home-skin--<?php echo e($skin['rarity']); ?>">
                                <img src="<?php echo e(asset('img/skins/' . $key . '/birthday/title.png')); ?>" alt="Habillage <?php echo e($skin['label']); ?>" width="609" height="430" loading="lazy" decoding="async">
                                <span class="home-skin__rarity"><?php echo e($rarities[$skin['rarity']]); ?></span>
                                <span class="home-skin__price"><?php echo gem_icon(); ?> <?php echo (int) skin_price($key, $skin['price']); ?></span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <?php if (referral_enabled() && 0 < $rewards['welcome']) : ?>
            <p class="home-play__referral">Un proche vous a donné son <strong>code de parrainage</strong> ? Saisissez-le à l'inscription : <strong><?php echo (int) $rewards['welcome']; ?> gemmes</strong> vous attendent.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="extension-promo">
    <span class="extension-promo__icon"><?php echo icon('chrome'); ?></span>
    <div>
        <h2>Ajoutez depuis n'importe quelle boutique</h2>
        <p>Avec l'extension Chrome, un clic sur la page d'un produit suffit pour l'ajouter à votre liste.</p>
    </div>
    <button type="button" class="btn btn--light" data-open="extension-dialog"><?php echo icon('download'); ?> L'extension</button>
</section>

<section class="home-cta">
    <h2>Prêt à faire votre liste ?</h2>
    <p>Deux minutes suffisent. Vos proches vous remercieront.</p>
    <div class="home-hero__actions">
        <button type="button" class="btn btn--primary btn--lg" data-open="signup-dialog"><?php echo icon('gift'); ?> Créer ma liste</button>
        <a class="btn btn--light btn--lg" href="index.php?page=comment-ca-marche"><?php echo icon('circle-info'); ?> Comment ça marche ?</a>
    </div>
</section>
