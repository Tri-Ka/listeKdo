<?php
/*
 * Gemmes et boutique d'habillages (« skins »).
 *
 *   Gemmes   gagnées avec les badges (badge.gems, ou valeur automatique selon le type et le niveau) et avec
 *            chaque action (idée, commentaire, réaction… : gem_actions(), réglable dans l'administration),
 *            dépensées dans la boutique. Solde = gemmes des badges + des actions - prix des achats.
 *   Skins    un habillage change le titre, les décorations et les couleurs d'une liste
 *            (img/skins/<skin>/<thème>/ : title.png, d1.png…). Il s'achète pour UN type de liste :
 *            l'article « neon/birthday » (skin_items()). Une liste porte un article de son type ; si son type
 *            change, elle reprend son apparence habituelle jusqu'au choix d'un article du nouveau type.
 *   Tables   user_skin (articles achetés « skin/thème », avec leur prix) ; liste_user.skin (article porté).
 *            Migration sql/2026-10-03-gemmes-boutique.sql.
 *
 * Planches d'origine : img/skins-sources/ (non envoyées sur le FTP).
 */

/**
 * Les colonnes et la table existent-elles ?
 */
function skins_enabled()
{
    return badges_enabled() && db_has_table('user_skin') && db_has_column('liste_user', 'skin') && db_has_column('badge', 'gems');
}

/**
 * Gemmes rapportées par défaut par un badge, selon son type et son niveau.
 * Un nouveau venu gagne une vingtaine de gemmes avec ses premières actions ; un habitué très actif, quelques centaines.
 */
function badge_default_gems($kind, $tier)
{
    $badge = array('bronze' => 5, 'silver' => 15, 'gold' => 40, 'legend' => 80);
    $trophy = array('bronze' => 50, 'silver' => 50, 'gold' => 100, 'legend' => 200);
    $values = 'trophy' === $kind ? $trophy : $badge;

    return isset($values[$tier]) ? $values[$tier] : 5;
}

/**
 * Gemmes d'un badge (valeur choisie dans l'administration, sinon valeur par défaut).
 */
function badge_gems($badge)
{
    return isset($badge['gems']) && 0 < (int) $badge['gems'] ? (int) $badge['gems'] : badge_default_gems($badge['kind'], $badge['tier']);
}

/* Expression SQL équivalente à badge_gems(), pour additionner en une requête. */
function badge_gems_sql()
{
    return "CASE WHEN b.gems > 0 THEN b.gems
        WHEN b.kind = 'trophy' AND b.tier = 'legend' THEN 200
        WHEN b.kind = 'trophy' AND b.tier = 'gold' THEN 100
        WHEN b.kind = 'trophy' THEN 50
        WHEN b.tier = 'legend' THEN 80
        WHEN b.tier = 'gold' THEN 40
        WHEN b.tier = 'silver' THEN 15
        ELSE 5 END";
}

/**
 * Actions qui rapportent des gemmes : clé => array(libellé, gemmes par défaut).
 * La valeur choisie dans l'administration est enregistrée dans kdo_setting (« gems_<clé> »).
 */
function gem_actions()
{
    return array(
        'ideas' => array('Idée ou élément de collection ajouté', 2),
        'comments' => array('Commentaire écrit', 1),
        'reactions' => array('Réaction donnée', 1),
        'gifts' => array('Cadeau réservé (idée ou élément)', 3),
        'groups' => array('Participation à une cagnotte', 3),
        'friends' => array('Ami ajouté', 1),
    );
}

/**
 * La table des réglages existe-t-elle ? (migration sql/2026-10-03-gemmes-actions.sql)
 */
function settings_enabled()
{
    return db_has_table('kdo_setting');
}

/**
 * Réglage du site, ou $default s'il n'est pas défini (réglages chargés une fois par page).
 */
function setting($name, $default)
{
    static $settings = null;
    if (null === $settings) {
        $settings = array();
        if (settings_enabled()) {
            foreach (db_all('SELECT name, value FROM kdo_setting') as $row) {
                $settings[$row['name']] = $row['value'];
            }
        }
    }

    return isset($settings[$name]) ? $settings[$name] : $default;
}

function setting_save($name, $value)
{
    return db_query('REPLACE INTO kdo_setting (name, value) VALUES (?, ?)', array($name, (string) $value));
}

/**
 * Gemmes par action, réglées (ou par défaut) : clé => gemmes.
 */
function gem_action_rates()
{
    $rates = array();
    foreach (gem_actions() as $key => $info) {
        $rates[$key] = max(0, (int) setting('gems_' . $key, $info[1]));
    }

    return $rates;
}

/**
 * Nombre de chaque action faite par une personne (une seule requête). Les éléments supprimés ne comptent plus.
 */
function gem_action_counts($userId)
{
    $id = (int) $userId;
    $sql = 'SELECT
        (SELECT COUNT(*) FROM liste_noel WHERE user_id = ' . $id . own_ideas_sql() . ')'
        . (items_enabled() ? ' + (SELECT COUNT(*) FROM liste_item i INNER JOIN liste_noel n ON n.id = i.product_id WHERE n.user_id = ' . $id . own_ideas_sql('n.') . ')' : '') . ' AS ideas,
        (SELECT COUNT(*) FROM comment WHERE user_id = ' . $id . ') AS comments,
        (SELECT COUNT(*) FROM reaction WHERE user_id = ' . $id . ') AS reactions,
        (SELECT COUNT(*) FROM liste_noel WHERE gifted_by = ' . $id . ')'
        . (items_enabled() ? ' + (SELECT COUNT(*) FROM liste_item WHERE gifted_by = ' . $id . ')' : '') . ' AS gifts,
        ' . (participations_enabled() ? '(SELECT COUNT(*) FROM liste_participation WHERE user_id = ' . $id . ')' : '0') . ' AS groups,
        (SELECT COUNT(DISTINCT u.id) FROM user_friend f INNER JOIN liste_user u ON u.code = f.friend_code WHERE f.user_id = ' . $id . ') AS friends';
    $row = db_one($sql);
    $counts = array();
    foreach (array_keys(gem_actions()) as $key) {
        $counts[$key] = $row ? (int) $row[$key] : 0;
    }

    return $counts;
}

/**
 * Gemmes gagnées (badges obtenus, même désactivés depuis, et actions), dépensées, et solde.
 */
function gems_of($userId)
{
    $gems = array('earned' => 0, 'badges' => 0, 'actions' => 0, 'referral' => 0, 'spent' => 0, 'balance' => 0);
    if (!skins_enabled()) {
        return $gems;
    }
    $row = db_one('SELECT SUM(' . badge_gems_sql() . ') AS n FROM user_badge ub INNER JOIN badge b ON b.id = ub.badge_id WHERE ub.user_id = ?', array((int) $userId));
    $gems['badges'] = $row ? (int) $row['n'] : 0;
    $rates = gem_action_rates();
    foreach (gem_action_counts($userId) as $key => $count) {
        $gems['actions'] += $count * $rates[$key];
    }
    // Parrainage : gemmes par filleul actif, et bonus de bienvenue si l'on a été parrainé.
    $gems['referral'] = 0;
    if (referral_enabled()) {
        $rewards = referral_rewards();
        $counts = referral_counts($userId);
        $gems['referral'] = $counts['active'] * $rewards['sponsor'];
        $me = db_one('SELECT referred_by FROM liste_user WHERE id = ?', array((int) $userId));
        if ($me && $me['referred_by']) {
            $gems['referral'] += $rewards['welcome'];
        }
    }
    $gems['earned'] = $gems['badges'] + $gems['actions'] + $gems['referral'];
    $row = db_one('SELECT SUM(price) AS n FROM user_skin WHERE user_id = ?', array((int) $userId));
    $gems['spent'] = $row ? (int) $row['n'] : 0;
    $gems['balance'] = max(0, $gems['earned'] - $gems['spent']);

    return $gems;
}

/**
 * Habillages disponibles.
 *   price   en gemmes, par défaut (modifiable dans Administration › Badges : réglage « price_<clé> »)
 *   rarity  commun, rare, epique, heroique, prestige, legendaire (du moins au plus rare ; couleur dans la boutique)
 *   themes  types de liste couverts (dossiers img/skins/<clé>/<thème>/)
 *   colors  variables CSS appliquées à la liste (mêmes noms que les blocs [data-theme] de css/app.css)
 */
function skins()
{
    return array(
        'pastel' => array(
            'label' => 'Pastel', 'price' => 200, 'rarity' => 'commun',
            'description' => 'Rose poudré, bleu ciel et rubans de satin.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#c9677d', 'brand-strong' => '#a94f65', 'brand-soft' => '#fbe9ec', 'page' => '#fffaf7', 'hero-from' => '#fbe3e0', 'hero-to' => '#fffaf7', 'confetti-1' => '#f6c4cf', 'confetti-2' => '#b8d4ee', 'confetti-3' => '#f5dfa0'),
        ),
        'calligraphie' => array(
            'label' => 'Calligraphie', 'price' => 200, 'rarity' => 'commun',
            'description' => 'Belles lettres, vert sapin et dorures.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#2f6b4f', 'brand-strong' => '#22533c', 'brand-soft' => '#e6f2ea', 'page' => '#fbfaf6', 'hero-from' => '#ecefe2', 'hero-to' => '#fbfaf6', 'confetti-1' => '#c9a227', 'confetti-2' => '#b33a3a', 'confetti-3' => '#2f6b4f'),
        ),
        'pirate' => array(
            'label' => 'Pirate', 'price' => 500, 'rarity' => 'rare',
            'description' => "Coffre au trésor et carte de l'île.",
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#8a4b20', 'brand-strong' => '#6b3714', 'brand-soft' => '#f4e6cf', 'page' => '#fbf6ec', 'hero-from' => '#ecd9b5', 'hero-to' => '#fbf6ec', 'confetti-1' => '#c0392b', 'confetti-2' => '#d4a017', 'confetti-3' => '#2c5f7c'),
        ),
        'farwest' => array(
            'label' => 'Far West', 'price' => 500, 'rarity' => 'rare',
            'description' => 'Saloon, cactus et étoile de shérif.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#b5651d', 'brand-strong' => '#8f4d12', 'brand-soft' => '#f8e8d2', 'page' => '#fdf8f0', 'hero-from' => '#f0dcbc', 'hero-to' => '#fdf8f0', 'confetti-1' => '#c0392b', 'confetti-2' => '#d4a017', 'confetti-3' => '#6b8e23'),
        ),
        'elegance' => array(
            'label' => 'Élégance', 'price' => 500, 'rarity' => 'rare',
            'description' => 'Bleu nuit et or, façon carton d’invitation.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#1f3a68', 'brand-strong' => '#142a50', 'brand-soft' => '#f3ead6', 'page' => '#fbf8f2', 'hero-from' => '#efe4cc', 'hero-to' => '#fbf8f2', 'confetti-1' => '#d4af37', 'confetti-2' => '#1f3a68', 'confetti-3' => '#e8c9a0'),
        ),
        'steampunk' => array(
            'label' => 'Steampunk', 'price' => 1000, 'rarity' => 'epique',
            'description' => 'Engrenages, laiton et dirigeables.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#9a5b24', 'brand-strong' => '#7a4416', 'brand-soft' => '#f3e4cc', 'page' => '#faf4ea', 'hero-from' => '#e8d4b0', 'hero-to' => '#faf4ea', 'confetti-1' => '#c08a3e', 'confetti-2' => '#2f6b6b', 'confetti-3' => '#b23a2a'),
        ),
        'cosmique' => array(
            'label' => 'Cosmique', 'price' => 1000, 'rarity' => 'epique',
            'description' => 'Chrome, planètes et petits robots.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#3b6fd8', 'brand-strong' => '#2a56b5', 'brand-soft' => '#e6efff', 'page' => '#f6f9ff', 'hero-from' => '#dde9ff', 'hero-to' => '#f6f9ff', 'confetti-1' => '#f9a8d4', 'confetti-2' => '#93c5fd', 'confetti-3' => '#c4b5fd'),
        ),
        'neon' => array(
            'label' => 'Néon', 'price' => 2000, 'rarity' => 'heroique',
            'description' => 'Lumières fluo et ambiance arcade.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#c026d3', 'brand-strong' => '#a21caf', 'brand-soft' => '#f7e3ff', 'page' => '#f7f3ff', 'hero-from' => '#e4dbff', 'hero-to' => '#f7f3ff', 'confetti-1' => '#22d3ee', 'confetti-2' => '#f472b6', 'confetti-3' => '#a78bfa'),
        ),
        'zombie' => array(
            'label' => 'Zombie', 'price' => 2000, 'rarity' => 'heroique',
            'description' => 'Slime, os et nounours recousu. Pour les fêtes qui ont du mordant.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#7c3aed', 'brand-strong' => '#6d28d9', 'brand-soft' => '#ecf8d8', 'page' => '#f5f8ef', 'hero-from' => '#dcedbd', 'hero-to' => '#f5f8ef', 'confetti-1' => '#84cc16', 'confetti-2' => '#a855f7', 'confetti-3' => '#22d3ee'),
        ),
        'gold' => array(
            'label' => 'Or', 'price' => 5000, 'rarity' => 'prestige',
            'description' => 'Ivoire et or massif : le grand luxe pour les plus fidèles.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#a87b1c', 'brand-strong' => '#8a6312', 'brand-soft' => '#f8efd9', 'page' => '#fdfaf3', 'hero-from' => '#f3e6c4', 'hero-to' => '#fdfaf3', 'confetti-1' => '#d4af37', 'confetti-2' => '#f5e6b8', 'confetti-3' => '#b8860b'),
        ),
        'diamant' => array(
            'label' => 'Diamant', 'price' => 10000, 'rarity' => 'legendaire',
            'description' => 'Couronnes, cristaux et diamants : l\'habillage ultime.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#6d4bd8', 'brand-strong' => '#5634bf', 'brand-soft' => '#efe9ff', 'page' => '#fbf9ff', 'hero-from' => '#ece5fb', 'hero-to' => '#fbf9ff', 'confetti-1' => '#d4af37', 'confetti-2' => '#a5d8ff', 'confetti-3' => '#f9a8d4'),
        ),
    );
}

function skin_rarities()
{
    return array('commun' => 'Commun', 'rare' => 'Rare', 'epique' => 'Épique', 'heroique' => 'Héroïque', 'prestige' => 'Prestige', 'legendaire' => 'Légendaire');
}

/**
 * Articles de la boutique regroupés par rareté (de commun à légendaire), pour leur affichage : rareté => articles.
 */
function shop_by_rarity($items)
{
    $groups = array();
    foreach (array_keys(skin_rarities()) as $rarity) {
        foreach ($items as $key => $item) {
            if ($item['rarity'] === $rarity) {
                $groups[$rarity][$key] = $item;
            }
        }
    }

    return $groups;
}

/**
 * Prix d'un habillage : réglé dans l'administration, sinon celui de skins().
 */
function skin_price($key, $default)
{
    return max(1, (int) setting('price_' . $key, $default));
}

/**
 * Articles de la boutique : un habillage pour un type de liste. Clé « skin/thème ».
 */
function skin_items()
{
    $items = array();
    foreach (skins() as $key => $skin) {
        $skin['price'] = skin_price($key, $skin['price']);
        foreach ($skin['themes'] as $theme) {
            $item = $skin;
            $item['skin'] = $key;
            $item['theme'] = $theme;
            $items[$key . '/' . $theme] = $item;
        }
    }

    return $items;
}

/* ---------- Cadres de photo et effets de compte à rebours ---------- */

/**
 * Les colonnes liste_user.frame et countdown_fx existent-elles ? (migration sql/2026-10-04-cadres-comptes-a-rebours.sql)
 * Les achats vont dans user_skin, avec les clés « frame/<clé> » et « countdown/<clé> ».
 */
function accessories_enabled()
{
    return skins_enabled() && db_has_column('liste_user', 'frame') && db_has_column('liste_user', 'countdown_fx');
}

/**
 * Cadres autour de la photo de profil : sur sa liste (grand format, avec ses effets), et en anneau sur les petites
 * photos (amis, commentaires, menu du compte). Tout est en CSS : bloc « Cadres » de css/app.css (.frame--<clé>).
 */
function frames()
{
    // Prix par défaut selon la rareté (accessory_default_prices()), réglable par article dans l'administration.
    return array(
        'trait' => array('label' => 'Trait', 'rarity' => 'commun', 'description' => 'Un anneau fin et net, aux couleurs de votre liste.'),
        'duo' => array('label' => 'Duo', 'rarity' => 'commun', 'description' => 'Un dégradé en deux tons, tout en douceur.'),
        'orbite' => array('label' => 'Orbite', 'rarity' => 'rare', 'description' => 'Un point de lumière fait le tour de votre photo.'),
        'aurore' => array('label' => 'Aurore', 'rarity' => 'epique', 'description' => 'Cyan, indigo et rose, avec un halo qui tourne.'),
        'neon' => array('label' => 'Néon', 'rarity' => 'epique', 'description' => 'Deux traits de lumière qui respirent.'),
        'cristal' => array('label' => 'Cristal', 'rarity' => 'heroique', 'description' => 'Un anneau de verre traversé par un reflet.'),
        'braise' => array('label' => 'Braise', 'rarity' => 'heroique', 'description' => 'Métal en fusion, lueur chaude et étincelles.'),
        'or' => array('label' => 'Or', 'rarity' => 'prestige', 'description' => 'Or brossé, filets fins et éclat qui passe.'),
        'prisme' => array('label' => 'Prisme', 'rarity' => 'legendaire', 'description' => 'Holographique, avec une traînée de lumière et des éclats en orbite.'),
        // Cadres illustrés : image img/frames/<clé>.webp (découpée par docker/cut-frames.py), lueurs animées en CSS.
        // Même rareté que l'habillage du même univers (skins()) : Pirate et Far West rares, Cosmique épique…
        'fleurs' => array('image' => true, 'label' => 'Cerisier', 'rarity' => 'commun', 'description' => 'Une couronne de fleurs de cerisier sur une branche dorée.'),
        'champetre' => array('image' => true, 'label' => 'Champêtre', 'rarity' => 'commun', 'description' => 'Feuillage et petites fleurs blanches, comme une couronne des champs.'),
        'pirate' => array('image' => true, 'label' => 'Pirate', 'rarity' => 'rare', 'description' => 'Gouvernail, ancre, drapeau à tête de mort et coffre au trésor.'),
        'farwest' => array('image' => true, 'label' => 'Far West', 'rarity' => 'rare', 'description' => 'Chapeau, étoile de shérif, fer à cheval et cactus.'),
        'etoiles' => array('image' => true, 'label' => 'Étoiles d’argent', 'rarity' => 'rare', 'description' => 'Un anneau d’argent tressé, semé d’étoiles qui scintillent.'),
        'lavande' => array('image' => true, 'label' => 'Lavande', 'rarity' => 'rare', 'description' => 'Brins de lavande, papillons et volutes d’or, avec quelques éclats.'),
        'romance' => array('image' => true, 'label' => 'Romance', 'rarity' => 'rare', 'description' => 'Roses, rubans et un cœur qui bat.'),
        'steampunk' => array('image' => true, 'label' => 'Steampunk', 'rarity' => 'epique', 'description' => 'Engrenages et horloge de cuivre, avec une lueur de chaudière.'),
        'cosmos' => array('image' => true, 'label' => 'Cosmos', 'rarity' => 'epique', 'description' => 'Planètes et lune autour de votre photo, sous un halo d’étoiles.'),
        'soleil-lune' => array('image' => true, 'label' => 'Soleil et Lune', 'rarity' => 'epique', 'description' => 'Le jour et la nuit se passent la lumière, tour à tour.'),
        'neon-retro' => array('image' => true, 'label' => 'Néon rétro', 'rarity' => 'heroique', 'description' => 'Tubes rose et cyan qui grésillent dans la nuit.'),
        'zombie' => array('image' => true, 'label' => 'Zombie', 'rarity' => 'heroique', 'description' => 'Bave verte fluo qui luit dans le noir. Âmes sensibles s’abstenir.'),
        'royal' => array('image' => true, 'label' => 'Royal', 'rarity' => 'prestige', 'description' => 'Couronne, rubis et or ciselé, traversés par un éclat.'),
        'diamant' => array('image' => true, 'label' => 'Diamant', 'rarity' => 'legendaire', 'description' => 'Cristaux irisés aux reflets changeants, éclats et étincelles.'),
    );
}

/**
 * Effets du compte à rebours de sa liste (vus par tous ses visiteurs), inspirés des types de liste et des habillages.
 * En CSS : bloc « Effets du compte à rebours » de css/app.css (.countdown[data-fx="<clé>"]).
 */
function countdown_effects()
{
    return array(
        'pastel' => array('label' => 'Pastel', 'rarity' => 'commun', 'description' => 'Tons poudrés et coins tout doux, comme l’habillage Pastel.'),
        'plume' => array('label' => 'Plume', 'rarity' => 'commun', 'description' => 'Papier crème et chiffres écrits à la main, façon faire-part.'),
        'flocons' => array('label' => 'Flocons', 'rarity' => 'rare', 'description' => 'Verre givré et neige fine qui tombe sans bruit.'),
        'fete' => array('label' => 'Fête', 'rarity' => 'rare', 'description' => 'Confettis aux couleurs de votre liste, qui virevoltent derrière les chiffres.'),
        'aurore' => array('label' => 'Aurore', 'rarity' => 'epique', 'description' => 'Un trait de lumière colorée fait le tour de chaque case.'),
        'neon' => array('label' => 'Néon', 'rarity' => 'epique', 'description' => 'Chiffres lumineux dans le noir, qui respirent, comme l’habillage Néon.'),
        'volets' => array('label' => 'Horloge à volets', 'rarity' => 'heroique', 'description' => 'Les volets d’une horloge de gare, qui basculent à chaque seconde.'),
        'chrome' => array('label' => 'Chrome', 'rarity' => 'heroique', 'description' => 'Métal poli et reflets qui glissent, façon habillage Cosmique.'),
        'or' => array('label' => 'Or', 'rarity' => 'prestige', 'description' => 'Or brossé, chiffres gravés et éclat qui passe, pour les grands jours.'),
        'pirate' => array('label' => 'Pirate', 'rarity' => 'rare', 'description' => 'Parchemin de carte au trésor et pièces d’or qui scintillent, comme l’habillage Pirate.'),
        'farwest' => array('label' => 'Far West', 'rarity' => 'rare', 'description' => 'Affiches « Wanted » au soleil du désert, poussière qui passe.'),
        'steampunk' => array('label' => 'Steampunk', 'rarity' => 'epique', 'description' => 'Plaques de laiton rivetées, engrenages qui tournent et vapeur qui s’échappe, comme le cadre Steampunk.'),
        'zombie' => array('label' => 'Zombie', 'rarity' => 'heroique', 'description' => 'Chiffres fluo dans le noir, bave verte qui coule et bulles toxiques.'),
        'galaxie' => array('label' => 'Galaxie', 'rarity' => 'legendaire', 'description' => 'Nébuleuse vivante, étoiles en mouvement et chiffres holographiques.'),
    );
}

/**
 * Familles d'accessoires : clé => array(libellé, colonne de liste_user où l'article porté est gardé, catalogue).
 */
function accessory_kinds()
{
    return array(
        'frame' => array('label' => 'Cadres', 'one' => 'cadre', 'column' => 'frame', 'catalog' => frames()),
        'countdown' => array('label' => 'Compte à rebours', 'one' => 'effet', 'column' => 'countdown_fx', 'catalog' => countdown_effects()),
    );
}

/**
 * Prix par défaut des cadres et des effets de compte à rebours : le même pour tous les articles d'une rareté.
 */
function accessory_default_prices()
{
    return array('commun' => 50, 'rare' => 120, 'epique' => 200, 'heroique' => 300, 'prestige' => 400, 'legendaire' => 500);
}

/**
 * Articles accessoires de la boutique, clé « frame/ruban » ou « countdown/neige ».
 * Prix par défaut selon la rareté, réglable article par article dans Administration › Badges (« price_frame_ruban »…).
 */
function accessory_items()
{
    $defaults = accessory_default_prices();
    $items = array();
    foreach (accessory_kinds() as $kind => $info) {
        foreach ($info['catalog'] as $key => $item) {
            $id = $kind . '/' . $key;
            $item['kind'] = $kind;
            $item['key'] = $key;
            $item['default_price'] = $defaults[$item['rarity']];
            $item['price'] = skin_price(accessory_price_setting($id), $item['default_price']);
            $items[$id] = $item;
        }
    }

    return $items;
}

/* Nom du réglage de prix d'un accessoire (sans « / », qui n'irait pas dans un nom de champ de formulaire). */
function accessory_price_setting($id)
{
    return str_replace('/', '_', $id);
}

/**
 * Accessoire porté par une personne ('' si aucun, ou s'il n'existe plus dans le catalogue).
 */
function accessory_worn($user, $kind)
{
    $kinds = accessory_kinds();
    if (!$user || !isset($kinds[$kind]) || !accessories_enabled()) {
        return '';
    }
    $key = isset($user[$kinds[$kind]['column']]) ? (string) $user[$kinds[$kind]['column']] : '';

    return isset($kinds[$kind]['catalog'][$key]) ? $key : '';
}

/**
 * Cadre décoratif autour d'une grande photo (sa liste, aperçu de la boutique), dans un parent en position relative.
 * Plusieurs couches, de plus en plus utilisées avec la rareté (css/app.css, « Cadres ») :
 *   .frame-back  derrière la photo : halo ou traînée de lumière (épique et plus) ;
 *   .frame       devant : l'anneau et jusqu'à trois points de lumière en orbite.
 */
function frame_html($key)
{
    if ('' === (string) $key) {
        return '';
    }
    $k = e($key);

    // Cadre illustré : l'image, un reflet qui la parcourt (masqué par l'image elle-même) et des étincelles.
    $frames = frames();
    if (!empty($frames[$key]['image'])) {
        $src = e(asset('img/frames/' . $key . '.webp'));

        return '<span class="frame-back frame-back--img frame-back--' . $k . '" aria-hidden="true"></span>'
            . '<span class="frame frame--img frame--' . $k . '" aria-hidden="true">'
            . '<img class="frame__img" src="' . $src . '" alt="" decoding="async">'
            // Masque en style inline : une url() dans le CSS serait résolue depuis css/, pas depuis la page.
            . '<span class="frame__glint" style="-webkit-mask-image: url(\'' . $src . '\'); mask-image: url(\'' . $src . '\')"></span>'
            . '<span class="frame__sparks"><i></i><i></i><i></i><i></i></span>'
            . '</span>';
    }

    return '<span class="frame-back frame-back--' . $k . '" aria-hidden="true"></span>'
        . '<span class="frame frame--' . $k . '" aria-hidden="true">'
        . '<span class="frame__ring"></span>'
        . '<span class="frame__orbit"><i></i><i></i><i></i></span>'
        . '</span>';
}

/**
 * Particules de l'effet du compte à rebours (le décor est en CSS, selon data-fx).
 */
function countdown_fx_html($key)
{
    if ('' === (string) $key) {
        return '';
    }

    return '<span class="countdown__fx" aria-hidden="true">' . str_repeat('<i></i>', 10) . '</span>';
}

/**
 * Applique l'habillage d'une liste à son thème (appelé par theme_of()) : titre, décorations, couleurs.
 * Sans habillage, ou si l'habillage ne couvre pas ce type de liste, le thème est rendu tel quel.
 */
function skin_apply($theme, $user)
{
    $theme['dir'] = 'deco/' . $theme['key'];
    $theme['skin'] = null;

    // Article porté (« neon/birthday ») : appliqué seulement s'il correspond au type de la liste.
    $item = $user && isset($user['skin']) ? (string) $user['skin'] : '';
    $skins = skins();
    $parts = explode('/', $item);
    $key = $parts[0];
    if (2 !== count($parts) || $parts[1] !== $theme['key'] || !isset($skins[$key]) || !in_array($theme['key'], $skins[$key]['themes'])) {
        return $theme;
    }

    $dir = 'skins/' . $key . '/' . $theme['key'];
    $files = array();
    for ($i = 1; $i <= 5; $i++) {
        if (file_exists(KDO_ROOT . '/img/' . $dir . '/d' . $i . '.png')) {
            $files[] = 'd' . $i;
        }
    }

    // d1, d3, d5 à gauche ; d2, d4 à droite (positions dans css/app.css, « Habillages »).
    $theme['left'] = array();
    $theme['right'] = array();
    foreach ($files as $i => $name) {
        $theme[0 === $i % 2 ? 'left' : 'right'][] = $name;
    }
    $theme['footer'] = isset($files[1]) ? $files[1] : (isset($files[0]) ? $files[0] : null);
    $theme['dir'] = $dir;
    $theme['skin'] = $key;
    $theme['colors'] = $skins[$key]['colors'];

    return $theme;
}

/**
 * Articles achetés par une personne : « skin/thème » => date d'achat.
 */
function skins_owned($userId)
{
    $owned = array();
    if (!skins_enabled() || !$userId) {
        return $owned;
    }
    foreach (db_all('SELECT skin, bought_at FROM user_skin WHERE user_id = ?', array((int) $userId)) as $row) {
        $owned[$row['skin']] = $row['bought_at'];
    }

    return $owned;
}

/**
 * Variables CSS d'un habillage, pour l'attribut style de <body>.
 */
function skin_style($theme)
{
    if (empty($theme['colors'])) {
        return '';
    }
    $style = '';
    foreach ($theme['colors'] as $name => $value) {
        $style .= '--' . $name . ': ' . $value . '; ';
    }

    return trim($style);
}

/**
 * Article choisi pour une liste : '' (classique) ou un article possédé par $me et du type de la liste, sinon arrêt.
 */
function skin_choice($me, $key, $listTheme, $back)
{
    $key = (string) $key;
    if ('' === $key) {
        return '';
    }
    $items = skin_items();
    $owned = skins_owned($me['id']);
    if (!isset($items[$key]) || !isset($owned[$key])) {
        fail("Cet habillage n'est pas dans votre collection.", $back, 403);
    }
    if ($items[$key]['theme'] !== $listTheme) {
        fail('Cet habillage est fait pour un autre type de liste.', $back, 409);
    }

    return $key;
}

/**
 * Accessoire choisi dans un formulaire (« Mon profil », « Paramètres de la liste ») : un de ceux achetés par la
 * personne connectée, ou '' (aucun). Sinon, refus 403.
 */
function accessory_choice($me, $kind, $key, $back)
{
    $key = (string) $key;
    if ('' === $key) {
        return '';
    }
    $kinds = accessory_kinds();
    $owned = skins_owned($me['id']);
    if (!isset($kinds[$kind]['catalog'][$key]) || !isset($owned[$kind . '/' . $key])) {
        fail("Cet article n'est pas dans votre collection.", $back, 403);
    }

    return $key;
}

/* ---------- Parrainage ---------- */

/**
 * La colonne liste_user.referred_by existe-t-elle ? (migration sql/2026-10-04-parrainage.sql)
 */
function referral_enabled()
{
    return skins_enabled() && db_has_column('liste_user', 'referred_by');
}

/**
 * Gemmes du parrainage (réglables dans l'administration) : array(parrain, filleul).
 */
function referral_rewards()
{
    return array(
        'sponsor' => max(0, (int) setting('gems_referral', 200)),
        'welcome' => max(0, (int) setting('gems_welcome', 50)),
    );
}

/**
 * Code de parrainage d'une personne : 6 caractères dérivés de son identifiant et de la clé du site
 * (rien à stocker). Sans lettres ambiguës (0/O, 1/I).
 */
function referral_code($user)
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $hash = hmac_sha1('referral|' . (int) $user['id'], app_secret());
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $alphabet[hexdec(substr($hash, $i * 2, 2)) % strlen($alphabet)];
    }

    return $code;
}

/**
 * Parrain correspondant à un code saisi (majuscules, espaces ignorés), ou null.
 * Les comptes sont peu nombreux : on compare au code de chacun.
 */
function referral_find($code)
{
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    if (6 !== strlen($code)) {
        return null;
    }
    foreach (db_all("SELECT * FROM liste_user WHERE password IS NOT NULL AND password <> ''") as $user) {
        if (referral_code($user) === $code) {
            return $user;
        }
    }

    return null;
}

/**
 * Filleuls d'une personne : array(total, actifs). Un filleul est actif dès qu'il a ajouté une idée.
 */
function referral_counts($userId)
{
    $counts = array('total' => 0, 'active' => 0);
    if (!referral_enabled()) {
        return $counts;
    }
    $row = db_one(
        'SELECT COUNT(*) AS total, SUM(EXISTS (SELECT 1 FROM liste_noel n WHERE n.user_id = u.id' . own_ideas_sql('n.') . ')) AS active
            FROM liste_user u WHERE u.referred_by = ?',
        array((int) $userId)
    );
    if ($row) {
        $counts['total'] = (int) $row['total'];
        $counts['active'] = (int) $row['active'];
    }

    return $counts;
}
