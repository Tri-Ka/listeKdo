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
        'ideas' => array('Idée ajoutée', 2),
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
        (SELECT COUNT(*) FROM liste_noel WHERE user_id = ' . $id . ') AS ideas,
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
    $gems = array('earned' => 0, 'badges' => 0, 'actions' => 0, 'spent' => 0, 'balance' => 0);
    if (!skins_enabled()) {
        return $gems;
    }
    $row = db_one('SELECT SUM(' . badge_gems_sql() . ') AS n FROM user_badge ub INNER JOIN badge b ON b.id = ub.badge_id WHERE ub.user_id = ?', array((int) $userId));
    $gems['badges'] = $row ? (int) $row['n'] : 0;
    $rates = gem_action_rates();
    foreach (gem_action_counts($userId) as $key => $count) {
        $gems['actions'] += $count * $rates[$key];
    }
    $gems['earned'] = $gems['badges'] + $gems['actions'];
    $row = db_one('SELECT SUM(price) AS n FROM user_skin WHERE user_id = ?', array((int) $userId));
    $gems['spent'] = $row ? (int) $row['n'] : 0;
    $gems['balance'] = max(0, $gems['earned'] - $gems['spent']);

    return $gems;
}

/**
 * Habillages disponibles.
 *   price   en gemmes
 *   rarity  commun, rare, epique, legendaire (couleur de l'étiquette dans la boutique)
 *   themes  types de liste couverts (dossiers img/skins/<clé>/<thème>/)
 *   colors  variables CSS appliquées à la liste (mêmes noms que les blocs [data-theme] de css/app.css)
 */
function skins()
{
    return array(
        'pastel' => array(
            'label' => 'Pastel', 'price' => 150, 'rarity' => 'commun',
            'description' => 'Rose poudré, bleu ciel et rubans de satin.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#c9677d', 'brand-strong' => '#a94f65', 'brand-soft' => '#fbe9ec', 'page' => '#fffaf7', 'hero-from' => '#fbe3e0', 'hero-to' => '#fffaf7', 'confetti-1' => '#f6c4cf', 'confetti-2' => '#b8d4ee', 'confetti-3' => '#f5dfa0'),
        ),
        'calligraphie' => array(
            'label' => 'Calligraphie', 'price' => 150, 'rarity' => 'commun',
            'description' => 'Belles lettres, vert sapin et dorures.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#2f6b4f', 'brand-strong' => '#22533c', 'brand-soft' => '#e6f2ea', 'page' => '#fbfaf6', 'hero-from' => '#ecefe2', 'hero-to' => '#fbfaf6', 'confetti-1' => '#c9a227', 'confetti-2' => '#b33a3a', 'confetti-3' => '#2f6b4f'),
        ),
        'pirate' => array(
            'label' => 'Pirate', 'price' => 200, 'rarity' => 'commun',
            'description' => "Coffre au trésor et carte de l'île.",
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#8a4b20', 'brand-strong' => '#6b3714', 'brand-soft' => '#f4e6cf', 'page' => '#fbf6ec', 'hero-from' => '#ecd9b5', 'hero-to' => '#fbf6ec', 'confetti-1' => '#c0392b', 'confetti-2' => '#d4a017', 'confetti-3' => '#2c5f7c'),
        ),
        'farwest' => array(
            'label' => 'Far West', 'price' => 200, 'rarity' => 'commun',
            'description' => 'Saloon, cactus et étoile de shérif.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#b5651d', 'brand-strong' => '#8f4d12', 'brand-soft' => '#f8e8d2', 'page' => '#fdf8f0', 'hero-from' => '#f0dcbc', 'hero-to' => '#fdf8f0', 'confetti-1' => '#c0392b', 'confetti-2' => '#d4a017', 'confetti-3' => '#6b8e23'),
        ),
        'elegance' => array(
            'label' => 'Élégance', 'price' => 300, 'rarity' => 'rare',
            'description' => 'Bleu nuit et or, façon carton d’invitation.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#1f3a68', 'brand-strong' => '#142a50', 'brand-soft' => '#f3ead6', 'page' => '#fbf8f2', 'hero-from' => '#efe4cc', 'hero-to' => '#fbf8f2', 'confetti-1' => '#d4af37', 'confetti-2' => '#1f3a68', 'confetti-3' => '#e8c9a0'),
        ),
        'steampunk' => array(
            'label' => 'Steampunk', 'price' => 450, 'rarity' => 'epique',
            'description' => 'Engrenages, laiton et dirigeables.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#9a5b24', 'brand-strong' => '#7a4416', 'brand-soft' => '#f3e4cc', 'page' => '#faf4ea', 'hero-from' => '#e8d4b0', 'hero-to' => '#faf4ea', 'confetti-1' => '#c08a3e', 'confetti-2' => '#2f6b6b', 'confetti-3' => '#b23a2a'),
        ),
        'cosmique' => array(
            'label' => 'Cosmique', 'price' => 450, 'rarity' => 'epique',
            'description' => 'Chrome, planètes et petits robots.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#3b6fd8', 'brand-strong' => '#2a56b5', 'brand-soft' => '#e6efff', 'page' => '#f6f9ff', 'hero-from' => '#dde9ff', 'hero-to' => '#f6f9ff', 'confetti-1' => '#f9a8d4', 'confetti-2' => '#93c5fd', 'confetti-3' => '#c4b5fd'),
        ),
        'neon' => array(
            'label' => 'Néon', 'price' => 650, 'rarity' => 'legendaire',
            'description' => 'Lumières fluo et ambiance arcade.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#c026d3', 'brand-strong' => '#a21caf', 'brand-soft' => '#f7e3ff', 'page' => '#f7f3ff', 'hero-from' => '#e4dbff', 'hero-to' => '#f7f3ff', 'confetti-1' => '#22d3ee', 'confetti-2' => '#f472b6', 'confetti-3' => '#a78bfa'),
        ),
        'zombie' => array(
            'label' => 'Zombie', 'price' => 650, 'rarity' => 'legendaire',
            'description' => 'Slime, os et nounours recousu. Pour les fêtes qui ont du mordant.',
            'themes' => array('birthday', 'noel', 'naissance', 'mariage', 'wishlist'),
            'colors' => array('brand' => '#7c3aed', 'brand-strong' => '#6d28d9', 'brand-soft' => '#ecf8d8', 'page' => '#f5f8ef', 'hero-from' => '#dcedbd', 'hero-to' => '#f5f8ef', 'confetti-1' => '#84cc16', 'confetti-2' => '#a855f7', 'confetti-3' => '#22d3ee'),
        ),
    );
}

function skin_rarities()
{
    return array('commun' => 'Commun', 'rare' => 'Rare', 'epique' => 'Épique', 'legendaire' => 'Légendaire');
}

/**
 * Articles de la boutique : un habillage pour un type de liste. Clé « skin/thème ».
 */
function skin_items()
{
    $items = array();
    foreach (skins() as $key => $skin) {
        foreach ($skin['themes'] as $theme) {
            $item = $skin;
            $item['skin'] = $key;
            $item['theme'] = $theme;
            $items[$key . '/' . $theme] = $item;
        }
    }

    return $items;
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
