<?php
// Page « Comment ça marche ? » (index.php?page=comment-ca-marche).
// Captures : img/guide/ (guide_image()), faites sur un compte de démonstration local, jamais sur de vraies données.
$sections = array(
    'ajouter' => 'Ajouter des idées',
    'partager' => 'Partager',
    'offrir' => 'Offrir',
    'plusieurs' => 'À plusieurs',
    'suggerer' => 'Suggérer',
    'echanger' => 'Échanger',
    'amis' => 'Amis',
    'personnaliser' => 'Personnaliser',
    'secondaires' => 'Listes secondaires',
    'badges' => 'Badges et boutique',
    'extension' => 'Extension Chrome',
    'questions' => 'Questions',
);
?>
<section class="guide-hero">
    <div class="guide-hero__text">
        <p class="guide-hero__eyebrow"><?php echo icon('gift'); ?> Liste de Kdo, simplement</p>
        <h1>Des cadeaux choisis avec envie, sans jamais gâcher la surprise.</h1>
        <p>Créez votre liste, partagez-la avec vos proches et laissez chacun réserver son cadeau en toute discrétion. C'est gratuit, sans publicité, et ça marche aussi bien sur téléphone que sur ordinateur.</p>
        <div class="guide-hero__actions">
            <?php if ($me) : ?>
                <a class="btn btn--primary btn--lg" href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>"><?php echo icon('gift'); ?> Voir ma liste</a>
                <?php if (onboarding_available()) : ?>
                    <button type="button" class="btn btn--light btn--lg" data-start-onboarding><?php echo icon('eye'); ?> Visite guidée</button>
                <?php endif; ?>
            <?php else : ?>
                <button type="button" class="btn btn--primary btn--lg" data-open="signup-dialog"><?php echo icon('gift'); ?> Créer ma liste</button>
                <button type="button" class="btn btn--light btn--lg" data-open="login-dialog">Se connecter</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="guide-hero__visual" aria-hidden="true">
        <img class="guide-hero__desktop" src="<?php echo e(asset('img/guide/liste.jpg')); ?>" alt="" width="1280" height="860" decoding="async">
        <img class="guide-hero__mobile" src="<?php echo e(asset('img/guide/mobile.jpg')); ?>" alt="" width="390" height="844" decoding="async">
    </div>
</section>

<section class="guide-steps" aria-label="Les étapes pour utiliser Liste de Kdo">
    <article class="guide-step">
        <span class="guide-step__number">1</span><span class="guide-step__icon"><?php echo icon('plus'); ?></span>
        <h2>Ajoutez vos envies</h2><p>Collez le lien d'un produit : son nom, sa photo et son prix sont récupérés automatiquement. Ou décrivez votre idée à la main.</p>
    </article>
    <article class="guide-step">
        <span class="guide-step__number">2</span><span class="guide-step__icon"><?php echo icon('share-nodes'); ?></span>
        <h2>Partagez votre lien</h2><p>Envoyez votre liste par WhatsApp, e-mail ou réseau social. Tout le monde peut la consulter, même sans compte.</p>
    </article>
    <article class="guide-step">
        <span class="guide-step__number">3</span><span class="guide-step__icon"><?php echo icon('gift'); ?></span>
        <h2>Ils réservent en secret</h2><p>Un proche indique ce qu'il offre. Les autres le voient pour éviter les doublons, mais vous, vous ne voyez rien.</p>
    </article>
</section>

<nav class="guide-toc" aria-label="Sommaire">
    <?php foreach ($sections as $anchor => $label) : ?>
        <a href="#<?php echo e($anchor); ?>"><?php echo e($label); ?></a>
    <?php endforeach; ?>
</nav>

<section class="guide-feature" id="ajouter">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('plus'); ?> Pour vous</p>
        <h2>Ajoutez vos idées en quelques secondes</h2>
        <p>Sur votre liste, cliquez sur <strong>« Ajouter une idée »</strong>.</p>
        <ul class="guide-list">
            <li><strong>Collez le lien</strong> du produit : le nom, la photo, la description et le prix se remplissent tout seuls. Si le site ne se laisse pas lire, complétez à la main.</li>
            <li><strong>Pas de lien ?</strong> Écrivez simplement votre idée et ajoutez une photo prise avec votre téléphone.</li>
            <li><strong>Le prix indicatif</strong> permet à vos proches de filtrer par budget (≤ 30 €, ≤ 50 €…) et de trier la liste.</li>
            <li><strong>Une collection</strong> regroupe plusieurs éléments à offrir séparément : les tomes d'une BD, des figurines, des livres d'une série…</li>
            <li>Le <?php echo icon('heart'); ?> sur une idée en fait un <strong>coup de cœur</strong>, pour montrer ce qui vous ferait le plus plaisir.</li>
            <li>Le menu <strong>« ⋯ »</strong> d'une idée permet de la modifier, de la supprimer, ou d'indiquer <strong>« Je l'ai reçu »</strong> : elle passe alors dans l'onglet « Reçus », que vous êtes seul à voir.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('ajout.jpg', "Fenêtre « Une nouvelle idée ? » remplie automatiquement à partir d'un lien", 620, 954, 'guide-shot--narrow'); ?>
    </figure>
</section>

<section class="guide-feature guide-feature--reverse" id="partager">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('share-nodes'); ?> Pour vous</p>
        <h2>Partagez votre liste avec vos proches</h2>
        <p>Le bouton <?php echo icon('share-nodes'); ?> en haut de votre liste ouvre la fenêtre de partage.</p>
        <ul class="guide-list">
            <li>Un <strong>lien court</strong> à copier, ou à envoyer directement par WhatsApp, Facebook ou X.</li>
            <li>N'importe qui peut <strong>consulter</strong> la liste avec ce lien. Pour réserver un cadeau, commenter ou réagir, il faut un compte (gratuit, et prêt en une minute).</li>
            <li>Votre <strong>code de parrainage</strong> est juste en dessous : un proche qui s'inscrit avec lui reçoit des gemmes de bienvenue, et vous aussi dès qu'il ajoute sa première idée.</li>
            <li>Besoin de discrétion ? Rendez votre liste <strong>privée</strong> dans les paramètres : elle n'est plus visible, même avec le lien, sauf par les <strong>personnes que vous choisissez</strong> : cochez vos amis juste en dessous de l'option, ou envoyez le <strong>lien d'invitation</strong> à ceux qui ne le sont pas encore.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('partage.jpg', 'Fenêtre « Partager la liste » avec le lien court et le code de parrainage', 440, 406, 'guide-shot--narrow'); ?>
    </figure>
</section>

<section class="guide-feature" id="offrir">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('gift'); ?> Pour vos proches</p>
        <h2>Réservez un cadeau, sans que la personne le sache</h2>
        <p>Sur la liste d'un ami, chaque idée encore libre affiche un bouton <strong>« Je l'offre »</strong>.</p>
        <ul class="guide-list">
            <li>Choisissez de <strong>l'offrir seul</strong>, ou de lancer une <strong>cagnotte à plusieurs</strong>.</li>
            <li>Les autres proches voient <strong>« Déjà offert »</strong> et savent qu'il faut choisir autre chose. Plus de doublons !</li>
            <li>La personne qui a fait la liste, elle, <strong>ne voit jamais</strong> ce qui est réservé, ni par qui : la surprise reste entière.</li>
            <li>Vous changez d'avis ? Cliquez sur <strong>« Vous l'offrez »</strong> pour libérer le cadeau.</li>
            <li>L'interrupteur <strong>« Voir les idées offertes »</strong> en haut de la page affiche ou masque les cadeaux déjà pris.</li>
        </ul>
    </div>
    <figure class="guide-feature__media guide-feature__media--stack">
        <?php echo guide_image('cartes.jpg', "La liste de Léa vue par un ami : idées à offrir, cadeau déjà offert, cagnotte et collection", 1280, 860); ?>
        <?php echo guide_image('offrir.jpg', "Fenêtre « Comment l'offrir ? » : seul ou à plusieurs", 440, 330, 'guide-shot--float'); ?>
    </figure>
</section>

<section class="guide-feature guide-feature--reverse" id="plusieurs">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('users'); ?> Pour vos proches</p>
        <h2>Les gros cadeaux à plusieurs, les collections pièce par pièce</h2>
        <ul class="guide-list">
            <li><strong>Cagnotte :</strong> chacun indique sa participation (facultative). Une barre montre où en est le total par rapport au prix, et le cadeau est complet quand la somme est atteinte.</li>
            <li>Les participants se retrouvent dans la fiche de l'idée, pour s'organiser en commentaire.</li>
            <li><strong>Collection :</strong> chaque élément se réserve un par un. On voit tout de suite ce qui reste à offrir (« 2 / 4 réservés »).</li>
            <li>Chaque élément peut avoir son <strong>lien vers la boutique</strong> et rapporte autant de gemmes qu'une idée. Vous pouvez le <strong>marquer comme reçu</strong>, puis le remettre dans la liste si besoin. Quand tous les éléments sont reçus, la collection passe dans « Reçus ».</li>
            <li>Le menu de votre compte › <strong>« Les cadeaux que j'offre »</strong> récapitule tout ce que vous avez prévu, liste par liste, avec la date de chaque événement.</li>
        </ul>
    </div>
    <figure class="guide-feature__media guide-feature__media--duo">
        <?php echo guide_image('cagnotte.jpg', 'Cagnotte : 230 € sur 320 €, avec la participation de chacun', 564, 342); ?>
        <?php echo guide_image('collection.jpg', 'Collection de livres : deux éléments réservés sur quatre', 384, 599); ?>
        <?php echo guide_image('mes-cadeaux.jpg', "Fenêtre « Les cadeaux que j'offre »", 620, 276, 'guide-shot--wide'); ?>
    </figure>
</section>

<section class="guide-feature" id="suggerer">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('lightbulb'); ?> Pour vos proches</p>
        <h2>Suggérez une idée, sans que la personne la voie</h2>
        <p>Vous savez ce qui ferait plaisir à un ami, mais ce n'est pas sur sa liste ? Sur sa liste, touchez <strong>« Suggérer une idée »</strong>.</p>
        <ul class="guide-list">
            <li>La personne qui a fait la liste <strong>ne la voit jamais</strong> : ni l'idée, ni les commentaires, ni les notifications. La surprise reste entière.</li>
            <li>Ses autres amis la voient, avec votre nom et une étiquette jaune <strong>« Suggestion »</strong>. Ils peuvent la réserver, lancer une cagnotte ou en discuter, comme n'importe quelle idée.</li>
            <li>L'onglet <strong>« Suggestions »</strong> les regroupe, et vous pouvez modifier ou supprimer les vôtres avec le menu « ⋯ ».</li>
            <li>Depuis l'<strong>extension Chrome</strong>, choisissez un ami dans « Ajouter à » pour lui suggérer le produit de la page.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('suggestion.jpg', "Une idée suggérée par Inès sur la liste de Léa, vue par Hugo", 384, 454, 'guide-shot--narrow'); ?>
    </figure>
</section>

<section class="guide-feature guide-feature--reverse" id="echanger">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('comments'); ?> Pour tout le monde</p>
        <h2>Commentez, réagissez, ne ratez rien</h2>
        <ul class="guide-list">
            <li>Cliquez sur une idée pour l'ouvrir en grand et <strong>laisser un commentaire</strong> : une question sur la taille, la couleur…</li>
            <li>Les <strong>réactions</strong> (j'adore, j'aime, HaHa !…) se donnent d'un clic sous chaque idée.</li>
            <li>La cloche <?php echo icon('bell'); ?> regroupe les <strong>notifications</strong> : nouvelles idées de vos amis, commentaires et réactions sur vos idées, badges obtenus…</li>
            <li>Des <strong>rappels</strong> arrivent un mois, une semaine et la veille de l'anniversaire de vos amis.</li>
            <li>L'onglet <strong>« Non lues »</strong> et « Tout marquer comme lu » aident à faire le tri.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('notifications.jpg', 'Panneau des notifications', 380, 888, 'guide-shot--narrow guide-shot--crop'); ?>
    </figure>
</section>

<section class="guide-feature" id="amis">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('user-plus'); ?> Pour tout le monde</p>
        <h2>Vos amis et leurs événements au même endroit</h2>
        <ul class="guide-list">
            <li>Sur la liste d'un proche, le bouton <strong>« Ajouter à mes amis »</strong> sous sa photo l'ajoute à vos amis.</li>
            <li>Vos amis apparaissent dans la colonne de gauche (ou en haut sur téléphone) : un clic pour passer d'une liste à l'autre.</li>
            <li>Ils sont <strong>triés par prochain événement</strong>, avec le nombre de jours restants.</li>
            <li>Chaque liste affiche un <strong>compte à rebours</strong> jusqu'au jour J : Noël, anniversaire, naissance ou mariage.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('amis.jpg', 'Fenêtre « Mes amis » avec le nombre de jours avant chaque événement', 620, 222); ?>
    </figure>
</section>

<section class="guide-feature guide-feature--reverse" id="personnaliser">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('palette'); ?> Pour vous</p>
        <h2>Une liste à votre image</h2>
        <p>Le bouton <?php echo icon('gear'); ?> ouvre les <strong>paramètres de la liste</strong>.</p>
        <ul class="guide-list">
            <li>Le <strong>type de liste</strong> : anniversaire, Noël, naissance, mariage ou simple wishlist. Les couleurs et les décorations changent avec lui.</li>
            <li>Un <strong>titre</strong> personnalisé (« Les 30 ans de Léa ») et la <strong>date de l'événement</strong>, pour le compte à rebours et les rappels.</li>
            <li>Les <strong>habillages</strong> achetés dans la boutique.</li>
            <li>L'option <strong>liste privée</strong>, avec le choix des <strong>amis qui peuvent quand même la voir</strong> et un <strong>lien d'invitation</strong> pour les autres.</li>
            <li>Dans <strong>« Mon profil »</strong> : votre photo, votre mot de passe, votre question secrète et un <strong>petit mot</strong> affiché en bas de votre liste.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('parametres.jpg', 'Fenêtre « Paramètres de la liste » : titre, type de liste, habillage, date, liste privée', 620, 728, 'guide-shot--narrow'); ?>
    </figure>
</section>

<section class="guide-feature" id="secondaires">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('child-reaching'); ?> Pour vous</p>
        <h2>Des listes pour vos enfants, votre bébé à venir…</h2>
        <ul class="guide-list">
            <li>Dans le menu de votre compte, <strong>« Nouvelle liste secondaire »</strong> crée une liste que vous gérez : pour un enfant, une naissance, un couple…</li>
            <li>Vous y ajoutez les idées comme sur la vôtre, et elle a son propre type, sa date et son lien de partage.</li>
            <li>Elle peut avoir <strong>plusieurs gestionnaires</strong>, par exemple les deux parents.</li>
            <li>Elle s'affiche sous votre liste (« Voir aussi »), et vos amis la retrouvent facilement.</li>
        </ul>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('secondaire.jpg', 'Liste de naissance de Jules, gérée par Léa, avec son compte à rebours', 1280, 860); ?>
    </figure>
</section>

<section class="guide-feature guide-feature--reverse" id="badges">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo gem_icon(); ?> Pour le plaisir</p>
        <h2>Badges, gemmes et boutique</h2>
        <ul class="guide-list">
            <li>Chaque action vous rapproche d'un <strong>badge</strong> : première idée, premier cadeau réservé, commentaires, amis, ancienneté… Certains sont secrets !</li>
            <li>Les badges et vos actions sur le site rapportent des <strong>gemmes</strong>. Le parrainage aussi.</li>
            <li>Dépensez-les dans la <strong>boutique</strong>, rangée en trois catégories : un nouvel <strong>habillage</strong> pour votre liste (pirate, pastel, far west, cosmique…), un <strong>cadre</strong> autour de votre photo, ou un <strong>effet</strong> sur votre compte à rebours.</li>
            <li>Plus un article est rare, plus il en jette : du simple anneau aux cadres holographiques, des bulles à la galaxie.</li>
            <li>Tout se gagne en utilisant le site : rien n'est payant.</li>
        </ul>
    </div>
    <figure class="guide-feature__media guide-feature__media--stack">
        <?php echo guide_image('badges.jpg', 'Vitrine des badges obtenus', 980, 774); ?>
        <?php echo guide_image('boutique.jpg', "La boutique d'habillages pour une liste d'anniversaire", 980, 1020, 'guide-shot--float guide-shot--crop'); ?>
    </figure>
</section>

<section class="guide-feature" id="extension">
    <div class="guide-feature__text">
        <p class="guide-feature__eyebrow"><?php echo icon('puzzle-piece'); ?> Sur ordinateur</p>
        <h2>L'extension Chrome : ajoutez depuis n'importe quelle boutique</h2>
        <ul class="guide-list">
            <li>Sur la page d'un produit, cliquez sur l'icône de l'extension : le nom, les photos, le prix et le lien sont déjà remplis.</li>
            <li>Choisissez la photo, la liste (la vôtre ou une liste secondaire), et c'est ajouté.</li>
            <li>Pour compléter une collection, choisissez-la dans <strong>« Dans cette liste »</strong> : le produit y est ajouté comme élément avec son nom et son lien.</li>
            <li>Elle fonctionne avec Chrome, Edge, Brave et Opera, sur ordinateur. La fenêtre d'installation explique tout pas à pas.</li>
        </ul>
        <p><button type="button" class="btn btn--light" data-open="extension-dialog"><?php echo icon('download'); ?> Installer l'extension</button></p>
    </div>
    <figure class="guide-feature__media">
        <?php echo guide_image('extension.jpg', "Fenêtre de l'extension Chrome, avec un produit prêt à être ajouté", 380, 670, 'guide-shot--narrow'); ?>
    </figure>
</section>

<section class="guide-faq" id="questions">
    <h2>Questions fréquentes</h2>
    <details>
        <summary>Est-ce que je peux voir qui m'offre quoi ?</summary>
        <p>Non, jamais. Sur votre propre liste, vous ne voyez ni les réservations, ni les cagnottes, ni les notifications de cadeaux. Seuls vos proches les voient.</p>
    </details>
    <details>
        <summary>Mes amis peuvent-ils ajouter des idées à ma liste ?</summary>
        <p>Oui, sous forme de suggestions. Mais vous ne les verrez jamais : elles ne sont visibles que de vos autres amis, qui peuvent vous les offrir. C'est fait pour garder la surprise.</p>
    </details>
    <details>
        <summary>Mes proches doivent-ils créer un compte ?</summary>
        <p>Pas pour regarder votre liste : le lien suffit. Pour réserver un cadeau, participer à une cagnotte, commenter ou réagir, il faut un compte. Il suffit d'un prénom et d'un mot de passe.</p>
    </details>
    <details>
        <summary>On m'a offert quelque chose qui était sur ma liste, sans le réserver. Que faire ?</summary>
        <p>Ouvrez le menu « ⋯ » de l'idée et choisissez « Je l'ai reçu ». Elle disparaît de la liste pour vos proches et se range dans votre onglet « Reçus ».</p>
    </details>
    <details>
        <summary>J'ai réservé un cadeau par erreur.</summary>
        <p>Cliquez sur « Vous l'offrez » sous l'idée, puis confirmez : le cadeau redevient disponible. Pour une cagnotte, ouvrez l'idée et choisissez « Me retirer ».</p>
    </details>
    <details>
        <summary>J'ai oublié mon mot de passe.</summary>
        <p>Dans la fenêtre de connexion, cliquez sur « Mot de passe oublié ? » et répondez à votre question secrète. Si vous n'en avez pas choisi, demandez à un administrateur de vous envoyer un lien pour le réinitialiser.</p>
    </details>
    <details>
        <summary>Comment supprimer mon compte ?</summary>
        <p>Dans « Mon profil », dépliez « Supprimer mon compte » en bas de la fenêtre et indiquez votre mot de passe. Votre liste, vos idées et vos commentaires sont effacés définitivement, ainsi que les listes secondaires que vous êtes seul à gérer. Les cadeaux que vous aviez réservés redeviennent disponibles pour les autres.</p>
    </details>
    <details>
        <summary>Est-ce que c'est payant ?</summary>
        <p>Non. Le site est gratuit et sans publicité, et les gemmes de la boutique se gagnent uniquement en l'utilisant.</p>
    </details>
</section>

<section class="guide-cta">
    <h2><?php echo $me ? 'À vous de jouer !' : 'Prêt à créer votre liste ?'; ?></h2>
    <div class="guide-hero__actions">
        <?php if ($me) : ?>
            <a class="btn btn--primary btn--lg" href="index.php?user=<?php echo e(rawurlencode($me['code'])); ?>"><?php echo icon('gift'); ?> Voir ma liste</a>
        <?php else : ?>
            <button type="button" class="btn btn--primary btn--lg" data-open="signup-dialog"><?php echo icon('gift'); ?> Créer ma liste</button>
        <?php endif; ?>
    </div>
    <p class="guide-back"><a href="index.php"><?php echo icon('arrow-left'); ?> Retour à l'accueil</a></p>
</section>
