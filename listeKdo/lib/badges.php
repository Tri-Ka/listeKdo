<?php
/*
 * Badges et trophées.
 *
 *   badge       définition (paramétrable dans l'administration) : un indicateur (« metric ») et un seuil.
 *   user_badge  badges obtenus (date, et « seen » : la fête « Nouveau badge ! » a été montrée).
 *
 * Un badge s'obtient quand l'indicateur atteint le seuil. Les indicateurs sont recalculés pour la personne
 * connectée à l'affichage de la page (badges_refresh(), au plus toutes les 30 secondes), puis les nouveaux
 * badges sont enregistrés. Un badge obtenu reste acquis, même si le seuil est remonté ensuite.
 * Les badges par défaut (badge_fixtures()) sont installés par le site lui-même (badges_install()),
 * à la première visite après la migration (badges_refresh()) ou depuis l'onglet Badges de l'administration.
 */

/**
 * Les tables existent-elles ? (migration sql/2026-10-03-badges.sql)
 */
function badges_enabled()
{
    return db_has_table('badge') && db_has_table('user_badge');
}

/**
 * Indicateurs disponibles : clé => libellé (pour l'administration).
 */
function badge_metrics()
{
    return array(
        'ideas' => 'Idées ajoutées',
        'ideas_photo' => 'Idées avec une photo',
        'ideas_link' => 'Idées avec un lien',
        'ideas_price' => 'Idées avec un prix',
        'favorites' => 'Coups de cœur',
        'collections' => 'Collections créées',
        'received' => 'Cadeaux reçus (marqués « Reçu »)',
        'gifts' => 'Cadeaux réservés (idées et éléments)',
        'recipients' => 'Personnes différentes gâtées',
        'groups_joined' => 'Participations à une cagnotte',
        'groups_started' => 'Cagnottes lancées',
        'groups_completed' => 'Cagnottes complètes (avec sa participation)',
        'comments' => 'Commentaires écrits',
        'reactions_given' => 'Réactions données',
        'reactions_received' => 'Réactions reçues sur ses idées',
        'friends' => 'Amis',
        'managed' => 'Listes secondaires gérées',
        'has_photo' => 'Photo de profil (1 = oui)',
        'has_secret' => 'Question secrète (1 = oui)',
        'has_message' => 'Petit mot (1 = oui)',
        'has_event_date' => "Date de l'événement (1 = oui)",
        'has_title' => 'Titre de liste (1 = oui)',
        'profile_complete' => 'Profil complet (0 à 5 : photo, question, petit mot, date, titre)',
        'years' => 'Années depuis sa première idée',
        'visits' => 'Jours de visite',
        'activity' => 'Activité totale (idées + commentaires + réactions + cadeaux)',
        'ideas_described' => 'Idées avec une description',
        'ideas_gifted' => 'Ses idées réservées par les autres',
        'comments_received' => 'Commentaires reçus sur ses idées',
        'reaction_variety' => 'Réactions différentes utilisées (sur 6)',
        'gift_value' => 'Valeur des cadeaux réservés (€, idées avec prix)',
        'group_amount' => 'Montant versé aux cagnottes (€)',
        'reaction_1' => "Réactions « j'adore » données",
        'reaction_2' => "Réactions « j'aime » données",
        'reaction_3' => 'Réactions « HaHa ! » données',
        'reaction_4' => 'Réactions « meh » données',
        'reaction_5' => "Réactions « j'aime pas » données",
        'reaction_6' => 'Réactions « BEEAARRGH !!! » données',
    );
}

/**
 * Niveaux : clé => libellé. Ils changent la couleur du badge.
 */
function badge_tiers()
{
    return array(
        'bronze' => 'Bronze',
        'silver' => 'Argent',
        'gold' => 'Or',
        'legend' => 'Légendaire',
    );
}

function badge_kinds()
{
    return array('badge' => 'Badge', 'trophy' => 'Trophée');
}

/**
 * Badges par défaut. code, type, niveau, indicateur, seuil, emoji, nom, description, secret, gemmes.
 * Le montant des gemmes est facultatif : zéro conserve le barème automatique du niveau.
 * Les trophées sont plus rares ; un badge « secret » reste caché (« ??? ») tant qu'il n'est pas obtenu.
 */
function badge_fixtures()
{
    $rows = array(
        // Sa liste
        array('first-idea', 'badge', 'bronze', 'ideas', 1, '💡', 'Première idée', 'Ajouter sa toute première idée cadeau.', 0),
        array('ideas-10', 'badge', 'silver', 'ideas', 10, '📝', 'Liste bien remplie', 'Avoir ajouté 10 idées.', 0),
        array('ideas-25', 'badge', 'gold', 'ideas', 25, '🎒', 'Hotte pleine', 'Avoir ajouté 25 idées.', 0),
        array('ideas-50', 'badge', 'legend', 'ideas', 50, '📜', "L'inventaire du Père Noël", 'Avoir ajouté 50 idées.', 0),
        array('ideas-photo-10', 'badge', 'silver', 'ideas_photo', 10, '📸', 'Œil de photographe', '10 idées avec une photo.', 0),
        array('ideas-link-10', 'badge', 'bronze', 'ideas_link', 10, '🔗', 'Lien direct', '10 idées avec le lien du produit.', 0),
        array('ideas-price-10', 'badge', 'bronze', 'ideas_price', 10, '💶', 'Budget maîtrisé', '10 idées avec un prix indicatif.', 0),
        array('favorites-3', 'badge', 'bronze', 'favorites', 3, '❤️', 'Cœur qui bat', 'Mettre 3 idées en coup de cœur.', 0),
        array('collection-1', 'badge', 'bronze', 'collections', 1, '🗂️', 'Collectionneur', 'Créer une collection (plusieurs éléments à offrir).', 0),
        array('collection-3', 'badge', 'silver', 'collections', 3, '🧩', 'Grand collectionneur', 'Créer 3 collections.', 0),
        array('received-1', 'badge', 'bronze', 'received', 1, '📦', 'Premier paquet', 'Marquer un cadeau comme reçu.', 0),
        array('received-10', 'badge', 'gold', 'received', 10, '🥳', 'Comblé', '10 cadeaux reçus.', 0),

        // Offrir
        array('gift-1', 'badge', 'bronze', 'gifts', 1, '🎀', 'Premier cadeau', 'Réserver un premier cadeau pour un proche.', 0),
        array('gift-5', 'badge', 'silver', 'gifts', 5, '🤲', 'Généreux', 'Réserver 5 cadeaux.', 0),
        array('gift-15', 'badge', 'gold', 'gifts', 15, '💎', 'Mécène', 'Réserver 15 cadeaux.', 0),
        array('gift-30', 'badge', 'legend', 'gifts', 30, '🧝', 'Lutin en chef', 'Réserver 30 cadeaux.', 0),
        array('recipients-3', 'badge', 'silver', 'recipients', 3, '🤗', 'Ami de tous', 'Gâter 3 personnes différentes.', 0),
        array('recipients-10', 'badge', 'gold', 'recipients', 10, '🎅', 'Père Noël du quartier', 'Gâter 10 personnes différentes.', 0),
        array('group-1', 'badge', 'bronze', 'groups_joined', 1, '🤝', "Esprit d'équipe", 'Participer à une cagnotte.', 0),
        array('group-5', 'badge', 'silver', 'groups_joined', 5, '🏛️', 'Pilier des cagnottes', 'Participer à 5 cagnottes.', 0),
        array('group-start-1', 'badge', 'bronze', 'groups_started', 1, '🚀', 'Lanceur de cagnotte', 'Lancer une cagnotte.', 0),
        array('group-start-3', 'badge', 'silver', 'groups_started', 3, '📣', 'Organisateur', 'Lancer 3 cagnottes.', 0),

        // Échanger
        array('comment-1', 'badge', 'bronze', 'comments', 1, '💬', 'Premier mot', 'Écrire un premier commentaire.', 0),
        array('comment-20', 'badge', 'silver', 'comments', 20, '🗣️', 'Bavard', 'Écrire 20 commentaires.', 0),
        array('comment-100', 'badge', 'gold', 'comments', 100, '📢', 'Pipelette', 'Écrire 100 commentaires.', 0),
        array('reaction-10', 'badge', 'bronze', 'reactions_given', 10, '👍', 'Réactif', 'Réagir 10 fois aux idées des autres.', 0),
        array('reaction-50', 'badge', 'silver', 'reactions_given', 50, '⚡', 'Toujours là', 'Réagir 50 fois.', 0),
        array('liked-10', 'badge', 'silver', 'reactions_received', 10, '⭐', 'Populaire', 'Recevoir 10 réactions sur ses idées.', 0),
        array('liked-50', 'badge', 'gold', 'reactions_received', 50, '🌟', 'Star', 'Recevoir 50 réactions sur ses idées.', 0),
        array('friends-5', 'badge', 'bronze', 'friends', 5, '👋', 'Sociable', 'Avoir 5 amis.', 0),
        array('friends-15', 'badge', 'silver', 'friends', 15, '🫶', 'Bien entouré', 'Avoir 15 amis.', 0),
        array('friends-30', 'badge', 'gold', 'friends', 30, '🌍', 'Réseau XXL', 'Avoir 30 amis.', 0),
        array('managed-1', 'badge', 'bronze', 'managed', 1, '🧸', 'Gestionnaire', "Gérer une liste secondaire (pour un enfant, un couple…).", 0),
        array('managed-3', 'badge', 'silver', 'managed', 3, '👑', 'Chef de famille', 'Gérer 3 listes secondaires.', 0),

        // Profil
        array('profile-photo', 'badge', 'bronze', 'has_photo', 1, '🖼️', 'Visage connu', 'Mettre une photo de profil.', 0),
        array('profile-secret', 'badge', 'bronze', 'has_secret', 1, '🔐', 'Coffre-fort', 'Choisir une question secrète.', 0),
        array('profile-message', 'badge', 'bronze', 'has_message', 1, '✉️', 'Petit mot', 'Écrire un petit mot en bas de sa liste.', 0),
        array('profile-date', 'badge', 'bronze', 'has_event_date', 1, '📅', 'Date cochée', "Indiquer la date de l'événement.", 0),
        array('profile-title', 'badge', 'bronze', 'has_title', 1, '🏷️', 'Titre sur mesure', 'Donner un titre à sa liste.', 0),
        array('loyal-1', 'badge', 'silver', 'years', 1, '🎂', 'Fidèle', 'Utiliser le site depuis plus d\'un an.', 0),
        array('loyal-5', 'badge', 'gold', 'years', 5, '🏅', 'Vétéran', 'Utiliser le site depuis plus de 5 ans.', 0),

        // Visites (jours distincts, après l'installation de statistiques-visites.sql)
        // Les récompenses sont explicites afin que la fidélité soit visible dans l'administration :
        // 5, 15 et 40 gemmes pour les badges, puis 200 pour le trophée.
        array('visit-1', 'badge', 'bronze', 'visits', 1, '👋', 'Première visite', 'Se connecter un premier jour.', 0, 5),
        array('visit-7', 'badge', 'silver', 'visits', 7, '🗓️', 'Rendez-vous pris', 'Se connecter 7 jours différents.', 0, 15),
        array('visit-30', 'badge', 'gold', 'visits', 30, '📆', 'Habitué des lieux', 'Se connecter 30 jours différents.', 0, 40),
        array('trophy-visit-100', 'trophy', 'legend', 'visits', 100, '🏆', 'Centenaire des visites', 'Se connecter 100 jours différents.', 0, 200),

        // Encore plus
        array('described-10', 'badge', 'bronze', 'ideas_described', 10, '🖋️', 'Plume précise', '10 idées avec une description.', 0),
        array('wanted-1', 'badge', 'bronze', 'ideas_gifted', 1, '🎉', 'Vœu exaucé', "Une de ses idées a été réservée par quelqu'un.", 0),
        array('wanted-10', 'badge', 'gold', 'ideas_gifted', 10, '🥰', 'Chouchou', '10 de ses idées réservées par les autres.', 0),
        array('discussed-10', 'badge', 'silver', 'comments_received', 10, '🗨️', 'Sujet de conversation', 'Recevoir 10 commentaires sur ses idées.', 0),
        array('emotions-6', 'badge', 'silver', 'reaction_variety', 6, '🎭', "Palette d'émotions", 'Utiliser les 6 réactions différentes.', 0),
        array('value-100', 'badge', 'silver', 'gift_value', 100, '💰', 'Portefeuille ouvert', 'Réserver 100 € de cadeaux.', 0),
        array('value-500', 'badge', 'gold', 'gift_value', 500, '🏦', 'Banque du Père Noël', 'Réserver 500 € de cadeaux.', 0),
        array('pot-50', 'badge', 'silver', 'group_amount', 50, '🪙', 'Petite pièce', 'Verser 50 € dans des cagnottes.', 0),
        array('pot-200', 'badge', 'gold', 'group_amount', 200, '🐷', 'Tirelire géante', 'Verser 200 € dans des cagnottes.', 0),
        array('ideas-100', 'badge', 'legend', 'ideas', 100, '📚', 'Encyclopédie des envies', 'Avoir ajouté 100 idées.', 0),
        array('comment-250', 'badge', 'legend', 'comments', 250, '🎙️', 'Le micro d\'or', 'Écrire 250 commentaires.', 0),
        array('loyal-3', 'badge', 'silver', 'years', 3, '🕯️', 'Habitué', 'Utiliser le site depuis plus de 3 ans.', 0),

        // Une série par réaction donnée : 5 (bronze), 20 (argent), 50 (or), puis un trophée légendaire à 100.
        array('react-adore-5', 'badge', 'bronze', 'reaction_1', 5, '😍', 'Cœur tendre', 'Donner 5 réactions « j\'adore ».', 0),
        array('react-adore-20', 'badge', 'silver', 'reaction_1', 20, '💞', 'Cœur d\'artichaut', 'Donner 20 réactions « j\'adore ».', 0),
        array('react-adore-50', 'badge', 'gold', 'reaction_1', 50, '💘', 'Grand romantique', 'Donner 50 réactions « j\'adore ».', 0),
        array('trophy-react-adore', 'trophy', 'legend', 'reaction_1', 100, '💖', 'Amour fou', 'Donner 100 réactions « j\'adore ».', 0),
        array('react-aime-5', 'badge', 'bronze', 'reaction_2', 5, '👍', 'Pouce levé', 'Donner 5 réactions « j\'aime ».', 0),
        array('react-aime-20', 'badge', 'silver', 'reaction_2', 20, '🙌', 'Bon public', 'Donner 20 réactions « j\'aime ».', 0),
        array('react-aime-50', 'badge', 'gold', 'reaction_2', 50, '🌞', 'Positive attitude', 'Donner 50 réactions « j\'aime ».', 0),
        array('trophy-react-aime', 'trophy', 'legend', 'reaction_2', 100, '😇', 'Bienveillance absolue', 'Donner 100 réactions « j\'aime ».', 0),
        array('react-haha-5', 'badge', 'bronze', 'reaction_3', 5, '😄', 'Sourire facile', 'Donner 5 réactions « HaHa ! ».', 0),
        array('react-haha-20', 'badge', 'silver', 'reaction_3', 20, '😂', 'Fou rire', 'Donner 20 réactions « HaHa ! ».', 0),
        array('react-haha-50', 'badge', 'gold', 'reaction_3', 50, '🤣', 'Pince-sans-rire', 'Donner 50 réactions « HaHa ! ».', 0),
        array('trophy-react-haha', 'trophy', 'legend', 'reaction_3', 100, '🃏', 'Roi du rire', 'Donner 100 réactions « HaHa ! ».', 0),
        array('react-meh-5', 'badge', 'bronze', 'reaction_4', 5, '😐', 'Bof', 'Donner 5 réactions « meh ».', 0),
        array('react-meh-20', 'badge', 'silver', 'reaction_4', 20, '🤷', 'Mouais', 'Donner 20 réactions « meh ».', 0),
        array('react-meh-50', 'badge', 'gold', 'reaction_4', 50, '🫤', 'Pas convaincu', 'Donner 50 réactions « meh ».', 0),
        array('trophy-react-meh', 'trophy', 'legend', 'reaction_4', 100, '🗿', 'Impassible', 'Donner 100 réactions « meh ».', 0),
        array('react-aimepas-5', 'badge', 'bronze', 'reaction_5', 5, '👎', 'Franc-parler', 'Donner 5 réactions « j\'aime pas ».', 0),
        array('react-aimepas-20', 'badge', 'silver', 'reaction_5', 20, '🙅', 'Difficile à satisfaire', 'Donner 20 réactions « j\'aime pas ».', 0),
        array('react-aimepas-50', 'badge', 'gold', 'reaction_5', 50, '😤', 'Critique en chef', 'Donner 50 réactions « j\'aime pas ».', 0),
        array('trophy-react-aimepas', 'trophy', 'legend', 'reaction_5', 100, '🧐', 'Juge suprême', 'Donner 100 réactions « j\'aime pas ».', 0),
        array('react-beurk-5', 'badge', 'bronze', 'reaction_6', 5, '🤢', 'Haut-le-cœur', 'Donner 5 réactions « BEEAARRGH !!! ».', 0),
        array('react-beurk-20', 'badge', 'silver', 'reaction_6', 20, '🤮', 'Estomac fragile', 'Donner 20 réactions « BEEAARRGH !!! ».', 0),
        array('react-beurk-50', 'badge', 'gold', 'reaction_6', 50, '☣️', 'Danger public', 'Donner 50 réactions « BEEAARRGH !!! ».', 0),
        array('trophy-react-beurk', 'trophy', 'legend', 'reaction_6', 100, '🧟', 'BEEAARRGH suprême', 'Donner 100 réactions « BEEAARRGH !!! ».', 0),

        // Trophées (rares)
        array('trophy-complete', 'trophy', 'gold', 'profile_complete', 5, '🏆', 'Profil impeccable', 'Photo, question secrète, petit mot, date et titre : tout est rempli.', 0),
        array('trophy-group-done', 'trophy', 'gold', 'groups_completed', 1, '🎯', 'Cagnotte bouclée', 'Participer à une cagnotte qui atteint son montant.', 0),
        array('trophy-active', 'trophy', 'legend', 'activity', 200, '🔥', 'Hyperactif', '200 actions : idées, commentaires, réactions et cadeaux.', 0),
        array('trophy-king', 'trophy', 'legend', 'gifts', 50, '🦄', 'Roi du Kdo', 'Un trophée secret… pour les plus généreux.', 1),
        array('trophy-pioneer', 'trophy', 'legend', 'years', 7, '🦕', 'Pionnier', 'Un trophée secret pour ceux qui étaient là dès le début.', 1),
        array('trophy-santa', 'trophy', 'legend', 'recipients', 20, '🛷', 'Tournée du Père Noël', 'Gâter 20 personnes différentes.', 0),
        array('trophy-star', 'trophy', 'gold', 'reactions_received', 100, '👑', 'Influenceur', 'Recevoir 100 réactions sur ses idées.', 0),
        array('trophy-patron', 'trophy', 'legend', 'gift_value', 1000, '🤑', 'Mécène royal', 'Un trophée secret pour les plus grands cœurs.', 1),
        array('trophy-allin', 'trophy', 'gold', 'ideas_gifted', 25, '🌈', 'Liste comblée', '25 de ses idées réservées par les autres.', 0),
    );

    $badges = array();
    foreach ($rows as $i => $row) {
        $badges[] = array(
            'code' => $row[0], 'kind' => $row[1], 'tier' => $row[2], 'metric' => $row[3], 'threshold' => $row[4],
            'emoji' => $row[5], 'name' => $row[6], 'description' => $row[7], 'secret' => $row[8],
            'gems' => isset($row[9]) ? $row[9] : 0, 'active' => 1, 'position' => ($i + 1) * 10,
        );
    }

    return $badges;
}

/**
 * Installe les badges par défaut qui manquent (repérés par leur code). Retourne le nombre ajouté.
 */
function badges_install()
{
    $existing = array();
    foreach (db_all('SELECT code FROM badge') as $row) {
        $existing[$row['code']] = true;
    }

    $added = 0;
    foreach (badge_fixtures() as $badge) {
        if (!isset($existing[$badge['code']])) {
            db_insert('badge', $badge);
            $added++;
        }
    }

    return $added;
}

/**
 * Tous les badges (l'administration), ou seulement les actifs, dans l'ordre d'affichage.
 */
function badges_all($activeOnly = true)
{
    return db_all('SELECT * FROM badge' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY position, id');
}

/**
 * Badges obtenus par une personne : badge_id => date.
 */
function badges_earned($userId)
{
    $earned = array();
    foreach (db_all('SELECT badge_id, earned_at FROM user_badge WHERE user_id = ?', array((int) $userId)) as $row) {
        $earned[(int) $row['badge_id']] = $row['earned_at'];
    }

    return $earned;
}

/* Résultat d'un « SELECT COUNT(*) AS n ». */
function badge_count($sql, $params)
{
    $row = db_one($sql, $params);

    return $row ? (int) $row['n'] : 0;
}

/**
 * Valeur de chaque indicateur pour une personne (une requête par indicateur, seulement à la mise à jour).
 */
function badge_metric_values($user)
{
    $id = (int) $user['id'];
    $values = array();

    $values['ideas'] = badge_count('SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ?', array($id));
    $values['ideas_photo'] = badge_count("SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND (image_url <> '' OR (file IS NOT NULL AND file <> ''))", array($id));
    $values['ideas_link'] = badge_count("SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND link <> ''", array($id));
    $values['ideas_price'] = prices_enabled() ? badge_count('SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND price IS NOT NULL', array($id)) : 0;
    $values['favorites'] = badge_count('SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND favorite = 1', array($id));
    $values['received'] = received_enabled() ? badge_count('SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND received_at IS NOT NULL', array($id)) : 0;
    $values['collections'] = items_enabled()
        ? badge_count('SELECT COUNT(DISTINCT i.product_id) AS n FROM liste_item i INNER JOIN liste_noel n ON n.id = i.product_id WHERE n.user_id = ?', array($id)) : 0;

    // Cadeaux : idées réservées seul + éléments de collection réservés.
    $values['gifts'] = badge_count('SELECT COUNT(*) AS n FROM liste_noel WHERE gifted_by = ?', array($id))
        + (items_enabled() ? badge_count('SELECT COUNT(*) AS n FROM liste_item WHERE gifted_by = ?', array($id)) : 0);

    // Personnes gâtées : propriétaires des idées réservées, des éléments réservés et des cagnottes rejointes.
    $owners = 'SELECT user_id AS owner FROM liste_noel WHERE gifted_by = ?';
    $params = array($id);
    if (items_enabled()) {
        $owners .= ' UNION SELECT n.user_id FROM liste_item i INNER JOIN liste_noel n ON n.id = i.product_id WHERE i.gifted_by = ?';
        $params[] = $id;
    }
    if (participations_enabled()) {
        $owners .= ' UNION SELECT n.user_id FROM liste_participation p INNER JOIN liste_noel n ON n.id = p.product_id WHERE p.user_id = ?';
        $params[] = $id;
    }
    $values['recipients'] = badge_count('SELECT COUNT(DISTINCT owner) AS n FROM (' . $owners . ') t WHERE owner <> ' . $id, $params);

    $values['groups_joined'] = 0;
    $values['groups_started'] = 0;
    $values['groups_completed'] = 0;
    if (participations_enabled()) {
        $values['groups_joined'] = badge_count('SELECT COUNT(*) AS n FROM liste_participation WHERE user_id = ?', array($id));
        // Lancée : personne n'est arrivé avant soi dans cette cagnotte.
        $values['groups_started'] = badge_count(
            'SELECT COUNT(*) AS n FROM liste_participation p WHERE p.user_id = ? AND NOT EXISTS (
                SELECT 1 FROM liste_participation q WHERE q.product_id = p.product_id
                AND (q.created_at < p.created_at OR (q.created_at = p.created_at AND q.user_id < p.user_id)))',
            array($id)
        );
        if (prices_enabled()) {
            $values['groups_completed'] = badge_count(
                'SELECT COUNT(*) AS n FROM liste_noel n WHERE n.price IS NOT NULL
                    AND n.id IN (SELECT product_id FROM liste_participation WHERE user_id = ?)
                    AND (SELECT SUM(amount) FROM liste_participation WHERE product_id = n.id) >= n.price',
                array($id)
            );
        }
    }

    $values['comments'] = badge_count('SELECT COUNT(*) AS n FROM comment WHERE user_id = ?', array($id));
    $values['reactions_given'] = badge_count('SELECT COUNT(*) AS n FROM reaction WHERE user_id = ?', array($id));
    $values['reactions_received'] = badge_count(
        'SELECT COUNT(*) AS n FROM reaction r INNER JOIN liste_noel n ON n.id = r.product_id WHERE n.user_id = ? AND r.user_id <> ?',
        array($id, $id)
    );
    $values['friends'] = badge_count('SELECT COUNT(*) AS n FROM user_friend WHERE user_id = ?', array($id));
    $values['managed'] = children_enabled()
        ? badge_count('SELECT COUNT(*) AS n FROM liste_manager m INNER JOIN liste_user u ON u.id = m.child_id WHERE m.user_id = ?', array($id)) : 0;

    $values['has_photo'] = '' !== (string) $user['pictureFile'] || '' !== (string) $user['pictureFileUrl'] ? 1 : 0;
    $values['has_secret'] = isset($user['secret_question']) && '' !== (string) $user['secret_question'] ? 1 : 0;
    $values['has_message'] = isset($user['message']) && '' !== trim((string) $user['message']) ? 1 : 0;
    $values['has_event_date'] = null !== birth_date($user) ? 1 : 0;
    $values['has_title'] = '' !== list_title($user) ? 1 : 0;
    $values['profile_complete'] = $values['has_photo'] + $values['has_secret'] + $values['has_message'] + $values['has_event_date'] + $values['has_title'];

    $first = db_one('SELECT MIN(created_at) AS first FROM liste_noel WHERE user_id = ?', array($id));
    $values['years'] = $first && $first['first'] ? max(0, (int) floor((time() - strtotime($first['first'])) / (365.25 * 86400))) : 0;

    // L'historique ne contient qu'une ligne par personne et par journée. Sans sa migration,
    // l'indicateur reste à zéro et les badges de visite ne peuvent pas être obtenus.
    $values['visits'] = visit_history_enabled()
        ? badge_count('SELECT COUNT(*) AS n FROM user_visit WHERE user_id = ?', array($id)) : 0;

    $values['activity'] = $values['ideas'] + $values['comments'] + $values['reactions_given'] + $values['gifts'];

    $values['ideas_described'] = badge_count("SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND description <> ''", array($id));
    $values['ideas_gifted'] = badge_count('SELECT COUNT(*) AS n FROM liste_noel WHERE user_id = ? AND gifted_by IS NOT NULL AND gifted_by <> ?', array($id, $id));
    $values['comments_received'] = badge_count(
        'SELECT COUNT(*) AS n FROM comment c INNER JOIN liste_noel n ON n.id = c.product_id WHERE n.user_id = ? AND c.user_id <> ?',
        array($id, $id)
    );
    $values['reaction_variety'] = badge_count('SELECT COUNT(DISTINCT type) AS n FROM reaction WHERE user_id = ?', array($id));
    for ($type = 1; $type <= 6; $type++) {
        $values['reaction_' . $type] = 0;
    }
    foreach (db_all('SELECT type, COUNT(*) AS n FROM reaction WHERE user_id = ? GROUP BY type', array($id)) as $row) {
        $values['reaction_' . (int) $row['type']] = (int) $row['n'];
    }
    $values['gift_value'] = prices_enabled()
        ? badge_count('SELECT FLOOR(COALESCE(SUM(price), 0)) AS n FROM liste_noel WHERE gifted_by = ?', array($id)) : 0;
    $values['group_amount'] = participations_enabled()
        ? badge_count('SELECT FLOOR(COALESCE(SUM(amount), 0)) AS n FROM liste_participation WHERE user_id = ?', array($id)) : 0;

    return $values;
}

/**
 * Met à jour les badges de la personne connectée (au plus toutes les 30 secondes).
 * Enregistre ceux qu'elle vient d'obtenir.
 */
function badges_refresh($user)
{
    if (!$user || !badges_enabled()) {
        return;
    }
    if (isset($_SESSION['kdo_badges_at']) && time() - $_SESSION['kdo_badges_at'] < 30) {
        return;
    }
    $_SESSION['kdo_badges_at'] = time();

    // Badges par défaut manquants (tables neuves, ou nouveaux badges après une mise à jour du site) : installés ici.
    $installed = db_one('SELECT COUNT(*) AS n FROM badge');
    if (!$installed || (int) $installed['n'] < count(badge_fixtures())) {
        badges_install();
    }
    $badges = badges_all();
    if (0 === count($badges)) {
        return;
    }
    $earned = badges_earned($user['id']);
    $values = badge_metric_values($user);

    // Tout le lot porte la même date : la notification (une seule par lot) retrouve ainsi le nombre de badges.
    $now = db_now();
    $best = null;
    $ranks = array('bronze' => 1, 'silver' => 2, 'gold' => 3, 'legend' => 4);
    foreach ($badges as $badge) {
        $value = isset($values[$badge['metric']]) ? $values[$badge['metric']] : 0;
        if (!isset($earned[(int) $badge['id']]) && $value >= (int) $badge['threshold']) {
            db_insert('user_badge', array('user_id' => (int) $user['id'], 'badge_id' => (int) $badge['id'], 'earned_at' => $now, 'seen' => 0));
            // Badge mis en avant : trophée d'abord, puis le niveau le plus haut.
            $rank = ('trophy' === $badge['kind'] ? 10 : 0) + (isset($ranks[$badge['tier']]) ? $ranks[$badge['tier']] : 0);
            if (null === $best || $rank > $best['rank']) {
                $best = array('rank' => $rank, 'id' => (int) $badge['id']);
            }
        }
    }

    // Notification : pour la personne (dans sa cloche) et pour ses amis.
    if (null !== $best) {
        db_insert('notification', array('author_id' => (int) $user['id'], 'product_id' => $best['id'], 'type' => NOTIF_BADGE, 'created_at' => $now));
    }
}

/**
 * Badges obtenus mais pas encore fêtés (fenêtre « Nouveau badge ! »), puis marqués comme vus.
 */
function badges_take_new($user)
{
    if (!$user || !badges_enabled()) {
        return array();
    }
    $rows = db_all(
        'SELECT b.* FROM user_badge ub INNER JOIN badge b ON b.id = ub.badge_id
            WHERE ub.user_id = ? AND ub.seen = 0 AND b.active = 1 ORDER BY b.position, b.id',
        array((int) $user['id'])
    );
    if (0 < count($rows)) {
        db_query('UPDATE user_badge SET seen = 1 WHERE user_id = ?', array((int) $user['id']));
    }

    return $rows;
}

/**
 * Vitrine d'une liste : badges actifs avec, pour chacun, 'earned' (date ou null)
 * et, pour sa propre vitrine, 'value' (progression vers le seuil).
 */
function badges_showcase($owner, $withProgress)
{
    if (!$owner || !badges_enabled()) {
        return array();
    }
    $earned = badges_earned($owner['id']);
    $values = $withProgress ? badge_metric_values($owner) : array();
    $showcase = array();

    foreach (badges_all() as $badge) {
        $id = (int) $badge['id'];
        $badge['earned'] = isset($earned[$id]) ? $earned[$id] : null;
        $badge['value'] = isset($values[$badge['metric']]) ? min((int) $values[$badge['metric']], (int) $badge['threshold']) : null;
        $showcase[] = $badge;
    }

    return $showcase;
}

/**
 * Nombre de badges obtenus (pastille sous le nom).
 */
function badges_count($owner)
{
    if (!$owner || !badges_enabled()) {
        return 0;
    }
    $row = db_one(
        'SELECT COUNT(*) AS n FROM user_badge ub INNER JOIN badge b ON b.id = ub.badge_id WHERE ub.user_id = ? AND b.active = 1',
        array((int) $owner['id'])
    );

    return $row ? (int) $row['n'] : 0;
}

/* Tri « à débloquer » : les plus avancés d'abord (les secrets à la fin). */
function badge_compare_progress($a, $b)
{
    $ra = $a['secret'] || null === $a['value'] ? -1 : $a['value'] / max(1, (int) $a['threshold']);
    $rb = $b['secret'] || null === $b['value'] ? -1 : $b['value'] / max(1, (int) $b['threshold']);
    if ($ra == $rb) {
        return 0;
    }

    return $ra > $rb ? -1 : 1;
}
