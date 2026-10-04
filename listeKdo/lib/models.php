<?php
/*
 * Accès aux données. Tables historiques (noms conservés) :
 *   liste_user    utilisateurs (code = identifiant public de la liste, utilisé dans les liens)
 *   liste_noel    idées cadeaux (« objets »)
 *   comment       commentaires sur une idée
 *   reaction      réactions (type 1 à 6) sur une idée
 *   notification  type 1 = commentaire, 2 = nouvelle idée, 3 = réaction, 4 = rappel, 5 = réservation, 6 = participation
 *   user_friend   amitiés (user_id -> friend_code)
 */

define('NOTIF_COMMENT', 1);
define('NOTIF_NEW_IDEA', 2);
define('NOTIF_REACTION', 3);
// Rappel d'événement d'un ami. author_id = l'ami, product_id = palier en jours (30, 7 ou 1), pas une idée.
define('NOTIF_EVENT', 4);
// Un ami réserve une idée (ou un élément de collection) / participe à un cadeau à plusieurs.
// Jamais montrées au propriétaire de la liste : il ne doit pas savoir qui offre quoi.
define('NOTIF_GIFT', 5);
define('NOTIF_PARTICIPATION', 6);
// Badges obtenus : author_id = la personne, product_id = le meilleur badge du lot (pas une idée).
// Une seule notification par lot (le nombre de badges du lot se retrouve par la date, voir notifications_add_badges()).
define('NOTIF_BADGE', 7);
// Parrainage : author_id = le filleul, product_id = 0. Montrée seulement au parrain (« X a ajouté sa première idée : +200 »).
define('NOTIF_REFERRAL', 8);

/**
 * Thèmes de liste. %s est remplacé par le nom du propriétaire.
 * Les décorations sont dans img/deco/<thème>/ (découpées dans img/elements*.png).
 *   event     : nom de l'événement (« Anniversaire dans 12 jours », compte à rebours)
 *   soon      : idem quand la date est unique et à venir (« Naissance prévue dans… »)
 *   date      : 'christmas' (25/12), 'yearly' (chaque année, depuis la date saisie), 'once' (date unique) ou 'none'
 *   reminder  : rappel aux amis (« Coline fête son anniversaire dans une semaine »), avec son emoji
 *   color     : couleur de la barre du navigateur (<meta name="theme-color">)
 */
/*
 * Textes des thèmes : %s est le nom du propriétaire. Une liste secondaire peut porter un titre
 * (« Mariage de Julie & Max ») plutôt qu'un prénom : son titre de page est alors son nom seul,
 * et elle utilise subtitle_list / note_list (sans le nom) quand ils existent.
 */
function themes()
{
    return array(
        'birthday' => array(
            'label' => 'Anniversaire',
            'title' => "Liste d'anniversaire",
            'heading' => 'Anniversaire de %s',
            'subtitle' => "Une liste d'idées cadeaux pour faire briller les yeux de %s ✨",
            'note' => "Rendez l'anniversaire de %s encore plus magique !",
            'subtitle_list' => "Une liste d'idées cadeaux pour un anniversaire inoubliable ✨",
            'note_list' => 'Rendez cet anniversaire encore plus magique !',
            'left' => array('gifts', 'star'),
            'right' => array('balloons', 'bunting'),
            'footer' => 'cake',
            'event' => 'Anniversaire',
            'soon' => 'Anniversaire',
            'date' => 'yearly',
            'reminder' => 'fête son anniversaire',
            'emoji' => '🎂',
            'color' => '#fff3dc',
        ),
        'noel' => array(
            'label' => 'Noël',
            'title' => 'Liste de Noël',
            'heading' => 'Noël de %s',
            'subtitle' => 'Les idées cadeaux de %s pour un Noël magique 🎄',
            'note' => 'Aidez le Père Noël à gâter %s !',
            'subtitle_list' => 'Des idées cadeaux pour un Noël magique 🎄',
            'note_list' => 'Aidez le Père Noël à faire des heureux !',
            'left' => array('ornament', 'candy', 'star'),
            'right' => array('gifts', 'gingerbread', 'snowflake'),
            'footer' => 'stocking',
            'event' => 'Noël',
            'soon' => 'Noël',
            'date' => 'christmas',
            'reminder' => 'attend Noël',
            'emoji' => '🎄',
            'color' => '#fdeeea',
        ),
        'naissance' => array(
            'label' => 'Naissance',
            'title' => 'Liste de naissance',
            'heading' => 'Naissance chez %s',
            'subtitle' => "Une liste toute douce pour accueillir bébé 🤍",
            'note' => "Merci de préparer avec nous l'arrivée de bébé !",
            'left' => array('mobile', 'bottle', 'heart'),
            'right' => array('teddy', 'rattle', 'pacifier'),
            'footer' => 'shoes',
            'event' => 'Naissance',
            'soon' => 'Naissance prévue',
            'date' => 'once',
            'reminder' => 'attend bébé',
            'emoji' => '👶',
            'color' => '#f1ecf8',
        ),
        'mariage' => array(
            'label' => 'Mariage',
            'title' => 'Liste de mariage',
            'heading' => 'Mariage de %s',
            'subtitle' => 'Les idées cadeaux de %s pour célébrer le grand jour 💍',
            'note' => 'Merci de partager ce jour de bonheur avec %s !',
            'subtitle_list' => 'Des idées cadeaux pour célébrer le grand jour 💍',
            'note_list' => 'Merci de partager ce jour de bonheur avec nous !',
            'left' => array('bouquet', 'hearts'),
            'right' => array('balloons', 'dove', 'glasses'),
            'footer' => 'rings',
            'event' => 'Mariage',
            'soon' => 'Mariage',
            'date' => 'once',
            'reminder' => 'se marie',
            'emoji' => '💍',
            'color' => '#f8f1e6',
        ),
        // Neutre : une liste d'envies sans événement (pas de date ni de compte à rebours).
        'wishlist' => array(
            'label' => 'Wishlist',
            'title' => 'Wishlist',
            'heading' => 'Wishlist de %s',
            'subtitle' => 'Les envies de %s, pour lui faire plaisir quand vous voulez 💫',
            'note' => 'Merci de faire plaisir à %s !',
            'subtitle_list' => 'Des envies à offrir quand vous voulez 💫',
            'note_list' => 'Merci pour vos attentions !',
            'left' => array('gifts', 'heart', 'leaf'),
            'right' => array('bag', 'tag', 'star'),
            'footer' => 'clipboard',
            'event' => 'Le grand jour',
            'soon' => 'Le grand jour',
            'date' => 'none',
            'reminder' => 'a de nouvelles envies',
            'emoji' => '🎁',
            'color' => '#f5efe5',
        ),
    );
}

/**
 * Thème envoyé par un formulaire s'il existe, sinon $default.
 */
function valid_theme($theme, $default)
{
    $themes = themes();

    return isset($themes[$theme]) ? $theme : $default;
}

function reaction_types()
{
    return array(
        1 => "j'adore",
        2 => "j'aime",
        3 => 'HaHa !',
        4 => 'meh',
        5 => "j'aime pas",
        6 => 'BEEAARRGH !!!',
    );
}

/**
 * Les anciennes données ont parfois été enregistrées avec les antislashs des magic quotes.
 */
function legacy_text($text)
{
    return str_replace(array("\\'", '\\"'), array("'", '"'), (string) $text);
}

/* ---------- Utilisateurs ---------- */

function user_find($id)
{
    return $id ? db_one('SELECT * FROM liste_user WHERE id = ?', array((int) $id)) : null;
}

function user_find_by_code($code)
{
    return '' !== (string) $code ? db_one('SELECT * FROM liste_user WHERE code = ?', array((string) $code)) : null;
}

function user_find_by_name($name)
{
    return db_one('SELECT * FROM liste_user WHERE nom = ?', array((string) $name));
}

/**
 * Utilisateurs indexés par id (une seule requête).
 */
function users_by_ids($ids)
{
    $ids = db_ids($ids);

    if (0 === count($ids)) {
        return array();
    }

    return db_index_by(db_all('SELECT * FROM liste_user WHERE id IN (?)', array($ids)), 'id');
}

function user_create($name, $password, $pictureFile, $theme)
{
    return db_insert('liste_user', array(
        'nom' => $name,
        'code' => random_token(),
        'password' => password_make($password),
        'theme' => $theme,
        'pictureFile' => $pictureFile,
    ));
}

/** Migration sql/2026-10-04-visite-guidee.sql : visite guidée affichée une seule fois par compte. */
function onboarding_available()
{
    return db_has_column('liste_user', 'onboarding_seen_at');
}

/**
 * Listes d'enfants gérées par un utilisateur (table liste_manager).
 */
function user_children($userId)
{
    if (!children_enabled()) {
        return array();
    }

    return db_all(
        'SELECT u.* FROM liste_user u INNER JOIN liste_manager m ON m.child_id = u.id WHERE m.user_id = ? ORDER BY u.nom ASC',
        array((int) $userId)
    );
}

/**
 * Parents qui gèrent la liste d'un enfant.
 */
function child_managers($childId)
{
    if (!children_enabled()) {
        return array();
    }

    return db_all(
        'SELECT u.* FROM liste_user u INNER JOIN liste_manager m ON m.user_id = u.id WHERE m.child_id = ? ORDER BY u.nom ASC',
        array((int) $childId)
    );
}

function is_child_list($user)
{
    return children_enabled() && null !== db_one('SELECT child_id FROM liste_manager WHERE child_id = ?', array((int) $user['id']));
}

function manager_add($childId, $userId)
{
    return db_query('REPLACE INTO liste_manager (child_id, user_id) VALUES (?, ?)', array((int) $childId, (int) $userId));
}

function manager_remove($childId, $userId)
{
    return db_query('DELETE FROM liste_manager WHERE child_id = ? AND user_id = ?', array((int) $childId, (int) $userId));
}

/**
 * L'utilisateur peut-il modifier cette liste ? (la sienne, ou celle d'un enfant qu'il gère)
 */
function can_manage($me, $owner)
{
    if (!$me || !$owner) {
        return false;
    }

    if ((int) $me['id'] === (int) $owner['id']) {
        return true;
    }

    return children_enabled()
        && null !== db_one('SELECT child_id FROM liste_manager WHERE child_id = ? AND user_id = ?', array((int) $owner['id'], (int) $me['id']));
}

/**
 * La colonne liste_user.is_private existe-t-elle ? (migration sql/2026-10-02-liste-privee.sql)
 */
function private_enabled()
{
    return db_has_column('liste_user', 'is_private');
}

/**
 * La colonne liste_user.list_title existe-t-elle ? (migration sql/2026-10-02-titre-liste.sql)
 */
function list_title_enabled()
{
    return db_has_column('liste_user', 'list_title');
}

/**
 * Titre choisi pour la liste, ou '' (titre par défaut du thème).
 */
function list_title($user)
{
    return $user && isset($user['list_title']) ? trim((string) $user['list_title']) : '';
}

function is_private_list($user)
{
    return $user && private_enabled() && !empty($user['is_private']);
}

/**
 * La table liste_viewer existe-t-elle ? (migration sql/2026-10-04-liste-privee-invites.sql)
 */
function viewers_enabled()
{
    return private_enabled() && db_has_table('liste_viewer');
}

/**
 * Ids des amis autorisés à voir une liste privée.
 */
function list_viewer_ids($listId)
{
    $ids = array();
    if (viewers_enabled()) {
        foreach (db_all('SELECT user_id FROM liste_viewer WHERE list_id = ?', array((int) $listId)) as $row) {
            $ids[] = (int) $row['user_id'];
        }
    }

    return $ids;
}

/**
 * Amis qu'on peut autoriser à voir une liste privée : ses amis, sans ses gestionnaires
 * (ils la voient déjà) ni les comptes sans mot de passe (listes secondaires : personne ne s'y connecte).
 */
function list_viewer_candidates($owner)
{
    $excluded = array((int) $owner['id']);
    foreach (child_managers($owner['id']) as $manager) {
        $excluded[] = (int) $manager['id'];
    }

    $candidates = array();
    foreach (user_friends($owner['id']) as $friend) {
        if ('' !== (string) $friend['password'] && !in_array((int) $friend['id'], $excluded)) {
            $candidates[] = $friend;
        }
    }

    return $candidates;
}

/**
 * Remplace les amis autorisés à voir une liste privée (seulement parmi list_viewer_candidates()).
 */
function list_viewers_set($owner, $ids)
{
    $allowed = array();
    foreach (list_viewer_candidates($owner) as $friend) {
        $allowed[] = (int) $friend['id'];
    }

    db_query('DELETE FROM liste_viewer WHERE list_id = ?', array((int) $owner['id']));
    foreach ($ids as $id) {
        if (in_array((int) $id, $allowed)) {
            db_query('REPLACE INTO liste_viewer (list_id, user_id) VALUES (?, ?)', array((int) $owner['id'], (int) $id));
        }
    }
}

/**
 * L'utilisateur peut-il voir cette liste ? Une liste privée n'est visible que de ceux qui la gèrent
 * et des amis qu'ils ont choisis (liste_viewer).
 */
function can_view($me, $owner)
{
    if (!is_private_list($owner) || can_manage($me, $owner)) {
        return true;
    }

    return $me && viewers_enabled()
        && null !== db_one('SELECT list_id FROM liste_viewer WHERE list_id = ? AND user_id = ?', array((int) $owner['id'], (int) $me['id']));
}

/**
 * Ids des listes privées que l'utilisateur ne peut pas voir (une seule requête, plus ses listes secondaires) :
 * toutes, sauf les siennes et celles où il est invité.
 */
function hidden_list_ids($me)
{
    if (!private_enabled()) {
        return array();
    }

    $mine = array($me ? (int) $me['id'] : 0);
    if ($me) {
        foreach (user_children($me['id']) as $child) {
            $mine[] = (int) $child['id'];
        }
    }

    $sql = 'SELECT id FROM liste_user WHERE is_private = 1 AND id NOT IN (?)';
    $params = array($mine);
    if ($me && viewers_enabled()) {
        $sql .= ' AND id NOT IN (SELECT list_id FROM liste_viewer WHERE user_id = ?)';
        $params[] = (int) $me['id'];
    }

    $ids = array();
    foreach (db_all($sql, $params) as $row) {
        $ids[] = (int) $row['id'];
    }

    return $ids;
}

/**
 * Retire d'une liste d'utilisateurs les listes privées que l'utilisateur ne peut pas voir.
 */
function visible_lists($me, $users)
{
    $hidden = hidden_list_ids($me);
    $visible = array();

    foreach ($users as $user) {
        if (!in_array((int) $user['id'], $hidden)) {
            $visible[] = $user;
        }
    }

    return $visible;
}

function child_create($parent, $name, $theme)
{
    $id = db_insert('liste_user', array(
        'nom' => $name,
        'code' => random_token(),
        // Pas de mot de passe : impossible de se connecter à ce compte, seul le parent le gère.
        'password' => '',
        'theme' => $theme,
    ));

    if ($id) {
        manager_add($id, $parent['id']);
    }

    return $id;
}

function user_friends($userId)
{
    return db_all(
        'SELECT u.* FROM liste_user u
            INNER JOIN user_friend f ON f.friend_code = u.code
            WHERE f.user_id = ?
            GROUP BY u.id
            ORDER BY u.nom ASC',
        array((int) $userId)
    );
}

function user_has_friend($userId, $friendCode)
{
    return null !== db_one('SELECT user_id FROM user_friend WHERE user_id = ? AND friend_code = ?', array((int) $userId, (string) $friendCode));
}

/**
 * L'amitié est réciproque : on l'ajoute dans les deux sens.
 */
function friend_add($user, $friend)
{
    if (!user_has_friend($user['id'], $friend['code'])) {
        db_insert('user_friend', array('user_id' => (int) $user['id'], 'friend_code' => $friend['code']));
    }

    if (!user_has_friend($friend['id'], $user['code'])) {
        db_insert('user_friend', array('user_id' => (int) $friend['id'], 'friend_code' => $user['code']));
    }
}

function friend_remove($user, $friend)
{
    db_query('DELETE FROM user_friend WHERE user_id = ? AND friend_code = ?', array((int) $user['id'], $friend['code']));
    db_query('DELETE FROM user_friend WHERE user_id = ? AND friend_code = ?', array((int) $friend['id'], $user['code']));

    // Plus amis : plus invités à voir la liste privée de l'autre.
    if (viewers_enabled()) {
        db_query(
            'DELETE FROM liste_viewer WHERE (list_id = ? AND user_id = ?) OR (list_id = ? AND user_id = ?)',
            array((int) $user['id'], (int) $friend['id'], (int) $friend['id'], (int) $user['id'])
        );
    }
}

/* ---------- Question secrète (mot de passe oublié) ---------- */

define('SECRET_MAX_FAILS', 5);
define('SECRET_LOCK_MINUTES', 15);

function secret_enabled()
{
    return db_has_column('liste_user', 'secret_question');
}

/**
 * Questions proposées. On peut aussi écrire sa propre question.
 */
function secret_questions()
{
    return array(
        'Enfance' => array(
            'Quel était le nom de votre premier animal de compagnie ?',
            'Quel était le nom de votre doudou ?',
            'Quel était votre jouet préféré quand vous étiez petit(e) ?',
            'Quel était votre surnom quand vous étiez petit(e) ?',
            "Quel est le prénom de votre meilleur(e) ami(e) d'enfance ?",
            'Quel était le nom de votre école primaire ?',
            'Quel est le prénom de votre premier(e) maître(sse) ?',
            'Dans quelle rue habitiez-vous enfant ?',
            'Quel était votre dessin animé préféré ?',
            'Quel était votre premier jeu vidéo ?',
            'Quel métier vouliez-vous faire plus tard ?',
        ),
        'Famille' => array(
            'Quel est le nom de jeune fille de votre mère ?',
            'Quel est le prénom de votre grand-père paternel ?',
            'Quel est le prénom de votre grand-mère maternelle ?',
            'Quel est le prénom de votre parrain ?',
            'Quel est le prénom de votre marraine ?',
            'Quel est le second prénom de votre père ?',
            'Dans quelle ville vos parents se sont-ils rencontrés ?',
            'Quel est le prénom de votre premier enfant ?',
            'Où avez-vous passé votre voyage de noces ?',
        ),
        'Lieux et souvenirs' => array(
            'Dans quelle ville êtes-vous né(e) ?',
            'Dans quel pays avez-vous fait votre premier voyage à l\'étranger ?',
            'Quelle est votre destination de vacances préférée ?',
            'Dans quelle ville avez-vous passé votre bac ?',
            'Quelle était la marque de votre première voiture ?',
            'Quel était le modèle de votre premier téléphone portable ?',
            'Quel était le nom de votre premier employeur ?',
            'Quel était votre premier petit boulot ?',
            'Quel est le prénom de votre premier amour ?',
        ),
        'Goûts' => array(
            'Quel est votre plat préféré ?',
            'Quel est votre dessert préféré ?',
            'Quel est votre fruit préféré ?',
            'Quelle est votre couleur préférée ?',
            'Quel est votre film préféré ?',
            'Quel est votre livre préféré ?',
            'Quelle est votre chanson préférée ?',
            'Quel est votre super-héros préféré ?',
            'Quel est votre sport préféré ?',
            'Quelle équipe de sport soutenez-vous ?',
            'De quel instrument de musique avez-vous joué ?',
        ),
        'Cadeaux et fêtes' => array(
            'Quel cadeau vous a le plus marqué(e) enfant ?',
            'Quel est votre plat de Noël préféré ?',
            "Quel est votre gâteau d'anniversaire préféré ?",
            'Quelle est votre fête préférée de l\'année ?',
            'Quel est le pire cadeau que vous ayez reçu ?',
            'Où avez-vous fêté vos 18 ans ?',
        ),
    );
}

/**
 * Réponse comparée sans majuscules, accents, espaces ni ponctuation : « Médor ! » = « medor ».
 * PHP 4 n'a ni mbstring ni intl : les lettres accentuées sont remplacées à la main.
 */
function secret_normalize($answer)
{
    $answer = strtr((string) $answer, array(
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'À' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Á' => 'a',
        'ç' => 'c', 'Ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'Ô' => 'o', 'Ö' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
        'ÿ' => 'y', 'ñ' => 'n', 'Ñ' => 'n', 'œ' => 'oe', 'Œ' => 'oe', 'æ' => 'ae', 'Æ' => 'ae', 'ß' => 'ss',
    ));

    return preg_replace('/[^a-z0-9]+/', '', strtolower($answer));
}

function secret_set($user, $question, $answer)
{
    return db_update('liste_user', array(
        'secret_question' => $question,
        'secret_answer' => password_make(secret_normalize($answer)),
        'secret_fails' => 0,
        'secret_locked_until' => null,
    ), array('id' => (int) $user['id']));
}

/**
 * Vérifie la réponse. Retourne 'ok', 'wrong' ou 'locked' (trop d'essais : blocage temporaire).
 */
function secret_check($user, $answer)
{
    if ($user['secret_locked_until'] && strtotime($user['secret_locked_until']) > time()) {
        return 'locked';
    }

    if (password_matches(secret_normalize($answer), $user['secret_answer'])) {
        db_update('liste_user', array('secret_fails' => 0, 'secret_locked_until' => null), array('id' => (int) $user['id']));
        return 'ok';
    }

    $fails = (int) $user['secret_fails'] + 1;
    $changes = array('secret_fails' => $fails);
    if ($fails >= SECRET_MAX_FAILS) {
        $changes = array('secret_fails' => 0, 'secret_locked_until' => date('Y-m-d H:i:s', time() + SECRET_LOCK_MINUTES * 60));
    }
    db_update('liste_user', $changes, array('id' => (int) $user['id']));

    return $fails >= SECRET_MAX_FAILS ? 'locked' : 'wrong';
}

/**
 * Question et réponse envoyées par un formulaire (liste ou question personnalisée).
 * Retourne array(question, réponse) ou null si rien n'est rempli ; arrête avec une erreur si incomplet.
 */
function secret_from_request($back)
{
    $question = input('secret_question');
    if ('__custom' === $question) {
        $question = input('secret_question_custom');
    }
    $answer = input('secret_answer');

    if ('' === $answer && ('' === $question || '__custom' === input('secret_question'))) {
        return null;
    }
    if ('' === $question || '' === $answer) {
        fail('Choisissez une question secrète et indiquez sa réponse.', $back);
    }
    if (strlen(secret_normalize($answer)) < 2) {
        fail('La réponse à la question secrète est trop courte.', $back);
    }

    return array(substr($question, 0, 255), $answer);
}

/* ---------- Date de l'événement ---------- */

/**
 * Date de naissance saisie dans le profil (colonne liste_user.event_date), ou null.
 */
function birth_date($user)
{
    if (!event_dates_enabled() || empty($user['event_date']) || '0000-00-00' === $user['event_date']) {
        return null;
    }

    return $user['event_date'];
}

/**
 * Prochaine date de l'événement d'une liste (timestamp à minuit), ou null.
 *   - Noël : toujours le 25 décembre ;
 *   - anniversaire : le prochain anniversaire, calculé depuis la date de naissance ;
 *   - naissance, mariage : la date prévue (date unique ; passée, plus de compte à rebours) ;
 *   - wishlist : pas d'événement.
 */
function event_next($user)
{
    $themes = themes();
    $mode = isset($user['theme']) && isset($themes[$user['theme']]) ? $themes[$user['theme']]['date'] : 'christmas';
    $today = mktime(0, 0, 0, (int) date('n'), (int) date('j'), (int) date('Y'));

    if ('none' === $mode) {
        return null;
    }

    if ('christmas' === $mode) {
        $month = 12;
        $day = 25;
    } else {
        $date = birth_date($user);
        if (null === $date) {
            return null;
        }
        list($year, $month, $day) = explode('-', $date);

        if ('once' === $mode) {
            $target = mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);
            return $target >= $today ? $target : null;
        }
    }

    $target = mktime(0, 0, 0, (int) $month, (int) $day, (int) date('Y'));
    if ($target < $today) {
        $target = mktime(0, 0, 0, (int) $month, (int) $day, (int) date('Y') + 1);
    }

    return $target;
}

/**
 * Âge fêté au prochain anniversaire (thème anniversaire uniquement), ou null.
 */
function event_age($user)
{
    $date = birth_date($user);
    $next = event_next($user);

    if ('birthday' !== $user['theme'] || null === $date || null === $next) {
        return null;
    }

    $age = (int) date('Y', $next) - (int) substr($date, 0, 4);

    return 0 < $age && $age < 130 ? $age : null;
}

/**
 * Nombre de jours avant l'événement, ou null.
 */
function event_days($user)
{
    $target = event_next($user);
    if (null === $target) {
        return null;
    }

    $today = mktime(0, 0, 0, (int) date('n'), (int) date('j'), (int) date('Y'));

    return (int) round(($target - $today) / 86400);
}

/**
 * Amis triés par prochain événement (les plus proches d'abord, sans date à la fin).
 */
function friends_by_event($friends)
{
    $dated = array();
    $undated = array();

    foreach ($friends as $friend) {
        $friend['event_days'] = event_days($friend);
        if (null === $friend['event_days']) {
            $undated[] = $friend;
        } else {
            $dated[] = $friend;
        }
    }

    usort($dated, 'compare_event_days');

    return array_merge($dated, $undated);
}

function compare_event_days($a, $b)
{
    if ($a['event_days'] === $b['event_days']) {
        return strcmp(strtolower($a['nom']), strtolower($b['nom']));
    }

    return $a['event_days'] < $b['event_days'] ? -1 : 1;
}

/* ---------- Idées cadeaux ---------- */

function object_find($id)
{
    return $id ? db_one('SELECT * FROM liste_noel WHERE id = ?', array((int) $id)) : null;
}

/**
 * Idées d'une liste, avec leurs commentaires, réactions et utilisateurs liés.
 * Six requêtes au total, quel que soit le nombre d'idées.
 */
function objects_for_user($userId)
{
    $objects = db_all('SELECT * FROM liste_noel WHERE user_id = ? ORDER BY created_at DESC, id DESC', array((int) $userId));

    if (0 === count($objects)) {
        return array();
    }

    $ids = array();
    foreach ($objects as $object) {
        $ids[] = (int) $object['id'];
    }

    $comments = db_all('SELECT * FROM comment WHERE product_id IN (?) ORDER BY id ASC', array($ids));
    $items = items_enabled() ? db_all('SELECT * FROM liste_item WHERE product_id IN (?) ORDER BY position ASC, id ASC', array($ids)) : array();
    $participations = participations_enabled() ? db_all('SELECT * FROM liste_participation WHERE product_id IN (?) ORDER BY created_at ASC', array($ids)) : array();
    $reactions = db_all('SELECT * FROM reaction WHERE product_id IN (?) ORDER BY id ASC', array($ids));

    $userIds = array();
    foreach ($comments as $comment) {
        $userIds[] = $comment['user_id'];
    }
    foreach ($reactions as $reaction) {
        $userIds[] = $reaction['user_id'];
    }
    foreach ($objects as $object) {
        $userIds[] = $object['gifted_by'];
    }
    foreach ($items as $item) {
        $userIds[] = $item['gifted_by'];
    }
    foreach ($participations as $participation) {
        $userIds[] = $participation['user_id'];
    }
    $users = users_by_ids($userIds);

    $byId = array();
    foreach ($objects as $object) {
        $object['comments'] = array();
        $object['reactions'] = array();
        $object['gifted_by_user'] = isset($users[$object['gifted_by']]) ? $users[$object['gifted_by']] : null;
        $byId[$object['id']] = object_prepare($object);
    }

    foreach ($comments as $comment) {
        if (isset($users[$comment['user_id']])) {
            $comment['user'] = $users[$comment['user_id']];
            $comment['content'] = legacy_text($comment['content']);
            $byId[$comment['product_id']]['comments'][] = $comment;
        }
    }

    foreach ($reactions as $reaction) {
        if (isset($users[$reaction['user_id']])) {
            $reaction['user'] = $users[$reaction['user_id']];
            $byId[$reaction['product_id']]['reactions'][$reaction['type']][] = $reaction;
        }
    }

    foreach ($items as $item) {
        $item['gifted_by_user'] = isset($users[$item['gifted_by']]) ? $users[$item['gifted_by']] : null;
        $byId[$item['product_id']]['items'][] = $item;
    }

    foreach ($participations as $participation) {
        if (isset($users[$participation['user_id']])) {
            $participation['user'] = $users[$participation['user_id']];
            $byId[$participation['product_id']]['participants'][] = $participation;
        }
    }

    foreach ($byId as $id => $object) {
        ksort($byId[$id]['reactions']);
        $byId[$id] = object_collection_state($byId[$id]);
    }

    return $byId;
}

function object_prepare($object)
{
    $object['nom'] = legacy_text($object['nom']);
    $object['description'] = trim(legacy_text($object['description']));
    $object['link'] = safe_url($object['link']);
    $object['image'] = object_image_url($object);
    $object['favorite'] = !empty($object['favorite']);
    if (!isset($object['items'])) {
        $object['items'] = array();
    }
    if (!isset($object['participants'])) {
        $object['participants'] = array();
    }
    $object['price'] = isset($object['price']) && null !== $object['price'] && 0 < (float) $object['price'] ? (float) $object['price'] : null;
    $object['received'] = !empty($object['received_at']);

    return $object;
}

function object_image_url($object)
{
    if ('' !== (string) $object['file']) {
        return 'uploads/img/' . $object['file'];
    }

    return safe_image_url($object['image_url']);
}

function object_reactions($objectId)
{
    $reactions = db_all('SELECT * FROM reaction WHERE product_id = ? ORDER BY id ASC', array((int) $objectId));
    $userIds = array();
    foreach ($reactions as $reaction) {
        $userIds[] = $reaction['user_id'];
    }
    $users = users_by_ids($userIds);

    $grouped = array();
    foreach ($reactions as $reaction) {
        if (isset($users[$reaction['user_id']])) {
            $reaction['user'] = $users[$reaction['user_id']];
            $grouped[$reaction['type']][] = $reaction;
        }
    }
    ksort($grouped);

    return $grouped;
}

function object_set_received($object, $received)
{
    return db_update('liste_noel', array('received_at' => $received ? db_now() : null), array('id' => (int) $object['id']));
}

function object_delete($object)
{
    $id = (int) $object['id'];

    db_query('DELETE FROM notification WHERE product_id = ? AND type NOT IN (?)', array($id, array(NOTIF_EVENT, NOTIF_BADGE, NOTIF_REFERRAL)));
    db_query('DELETE FROM comment WHERE product_id = ?', array($id));
    db_query('DELETE FROM reaction WHERE product_id = ?', array($id));
    if (items_enabled()) {
        db_query('DELETE FROM liste_item WHERE product_id = ?', array($id));
    }
    if (participations_enabled()) {
        db_query('DELETE FROM liste_participation WHERE product_id = ?', array($id));
    }
    db_query('DELETE FROM liste_noel WHERE id = ? AND user_id = ?', array($id, (int) $object['user_id']));
}

/**
 * Coup de cœur du propriétaire sur une de ses idées.
 * Nécessite la colonne liste_noel.favorite (sql/2026-10-01-coups-de-coeur.sql).
 */
function object_set_favorite($object, $favorite)
{
    return db_update('liste_noel', array('favorite' => $favorite ? 1 : 0), array('id' => (int) $object['id'], 'user_id' => (int) $object['user_id']));
}

/* ---------- Collections (éléments d'une idée, table liste_item) ---------- */

/**
 * La table liste_item existe-t-elle ? (migration sql/2026-10-01-collections.sql)
 */
function items_enabled()
{
    return db_has_table('liste_item');
}

/* Fonctionnalités de la migration sql/2026-10-01-prix-participations-enfants.sql */

function prices_enabled()
{
    return db_has_column('liste_noel', 'price');
}

function received_enabled()
{
    return db_has_column('liste_noel', 'received_at');
}

function event_dates_enabled()
{
    return db_has_column('liste_user', 'event_date');
}

function children_enabled()
{
    return db_has_table('liste_manager');
}

function participations_enabled()
{
    return db_has_table('liste_participation');
}

/**
 * Éléments d'une idée, avec la personne qui offre chacun.
 */
function object_items($objectId)
{
    if (!items_enabled()) {
        return array();
    }

    $items = db_all('SELECT * FROM liste_item WHERE product_id = ? ORDER BY position ASC, id ASC', array((int) $objectId));
    $userIds = array();
    foreach ($items as $item) {
        $userIds[] = $item['gifted_by'];
    }
    $users = users_by_ids($userIds);

    foreach ($items as $i => $item) {
        $items[$i]['gifted_by_user'] = isset($users[$item['gifted_by']]) ? $users[$item['gifted_by']] : null;
    }

    return $items;
}

/**
 * Compteurs d'une collection : is_collection, items_total, items_gifted, complete.
 * Une collection compte comme « offerte » (onglets, vignette grisée) quand tous ses éléments sont réservés.
 */
function object_collection_state($object)
{
    $object['is_collection'] = 0 < count($object['items']);
    $object['items_total'] = count($object['items']);
    $object['items_gifted'] = 0;

    foreach ($object['items'] as $item) {
        if (null !== $item['gifted_by']) {
            $object['items_gifted']++;
        }
    }

    // Cadeau à plusieurs : complet quand les montants annoncés atteignent le prix.
    $object['is_group'] = !$object['is_collection'] && 0 < count($object['participants']);
    $object['group_total'] = 0;
    foreach ($object['participants'] as $participant) {
        $object['group_total'] += (float) $participant['amount'];
    }

    if ($object['is_collection']) {
        $object['complete'] = $object['items_gifted'] === $object['items_total'];
    } elseif ($object['is_group']) {
        $object['complete'] = null !== $object['price'] && $object['group_total'] >= $object['price'];
    } else {
        $object['complete'] = null !== $object['gifted_by'];
    }

    return $object;
}

/**
 * Charge éléments et participants d'une seule idée (après une action AJAX), puis calcule son état.
 */
function object_full($object)
{
    $object = object_prepare($object);
    $object['items'] = object_items($object['id']);
    $object['participants'] = object_participants($object['id']);
    $object['gifted_by_user'] = $object['gifted_by'] ? user_find($object['gifted_by']) : null;

    return object_collection_state($object);
}

/* ---------- Cadeaux à plusieurs (table liste_participation) ---------- */

function object_participants($objectId)
{
    if (!participations_enabled()) {
        return array();
    }

    $rows = db_all('SELECT * FROM liste_participation WHERE product_id = ? ORDER BY created_at ASC', array((int) $objectId));
    $userIds = array();
    foreach ($rows as $row) {
        $userIds[] = $row['user_id'];
    }
    $users = users_by_ids($userIds);

    $participants = array();
    foreach ($rows as $row) {
        if (isset($users[$row['user_id']])) {
            $row['user'] = $users[$row['user_id']];
            $participants[] = $row;
        }
    }

    return $participants;
}

function participation_set($objectId, $userId, $amount)
{
    return db_query(
        /* Mise à jour du montant sans toucher à la date d'arrivée (elle désigne qui a lancé la cagnotte). */
        'INSERT INTO liste_participation (product_id, user_id, amount, created_at) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount)',
        array((int) $objectId, (int) $userId, $amount, db_now())
    );
}

function participation_remove($objectId, $userId)
{
    return db_query('DELETE FROM liste_participation WHERE product_id = ? AND user_id = ?', array((int) $objectId, (int) $userId));
}

/**
 * Montant saisi (« 29,90 », « 30 € ») -> nombre, ou null si vide ou invalide.
 */
function parse_amount($value)
{
    $value = str_replace(array(' ', "\xc2\xa0", '€', ','), array('', '', '', '.'), trim((string) $value));

    if ('' === $value || !preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
        return null;
    }

    $amount = (float) $value;

    return 0 < $amount && $amount < 1000000 ? $amount : null;
}

/**
 * Enregistre les éléments d'une idée depuis le formulaire.
 * $names et $ids viennent des champs items[] et item_ids[] (même ordre) ;
 * les éléments existants gardent leur réservation, ceux retirés du formulaire sont supprimés.
 */
function items_save($object, $names, $ids)
{
    if (!items_enabled()) {
        return;
    }

    $existing = db_index_by(db_all('SELECT * FROM liste_item WHERE product_id = ?', array((int) $object['id'])), 'id');
    $kept = array();
    $position = 0;

    foreach ($names as $i => $name) {
        $name = trim((string) $name);
        if ('' === $name) {
            continue;
        }
        $name = substr($name, 0, 255);
        $id = isset($ids[$i]) ? (int) $ids[$i] : 0;

        if ($id && isset($existing[$id])) {
            db_update('liste_item', array('nom' => $name, 'position' => $position), array('id' => $id));
            $kept[$id] = true;
        } else {
            db_insert('liste_item', array('product_id' => (int) $object['id'], 'nom' => $name, 'position' => $position));
        }
        $position++;
    }

    foreach ($existing as $id => $item) {
        if (!isset($kept[$id])) {
            db_query('DELETE FROM liste_item WHERE id = ?', array((int) $id));
        }
    }
}

/**
 * Lit les champs items[] / item_ids[] du formulaire d'idée.
 */
function items_from_request()
{
    $names = isset($_POST['items']) && is_array($_POST['items']) ? array_values($_POST['items']) : array();
    $ids = isset($_POST['item_ids']) && is_array($_POST['item_ids']) ? array_values($_POST['item_ids']) : array();
    $filled = array();

    foreach ($names as $name) {
        if ('' !== trim((string) $name)) {
            $filled[] = $name;
        }
    }

    return array($names, $ids, count($filled));
}

function reaction_set($objectId, $userId, $type)
{
    $current = db_one('SELECT type FROM reaction WHERE product_id = ? AND user_id = ?', array((int) $objectId, (int) $userId));

    db_query('DELETE FROM reaction WHERE product_id = ? AND user_id = ?', array((int) $objectId, (int) $userId));
    db_query('DELETE FROM notification WHERE product_id = ? AND author_id = ? AND type = ?', array((int) $objectId, (int) $userId, NOTIF_REACTION));

    // Cliquer à nouveau sur la même réaction la retire.
    if ($current && (int) $current['type'] === (int) $type) {
        return;
    }

    db_insert('reaction', array('product_id' => (int) $objectId, 'user_id' => (int) $userId, 'type' => (int) $type));
    notify($userId, $objectId, NOTIF_REACTION);
}

function notify($authorId, $objectId, $type)
{
    db_insert('notification', array(
        'author_id' => (int) $authorId,
        'product_id' => (int) $objectId,
        'type' => (int) $type,
        'created_at' => db_now(),
    ));
}

/**
 * Notification de don (NOTIF_GIFT ou NOTIF_PARTICIPATION) : une seule par ami et par idée.
 * $on = true la crée si besoin (en gardant sa date d'origine), false la supprime.
 */
function notify_gift($authorId, $objectId, $type, $on, $refresh = false)
{
    $params = array((int) $objectId, (int) $authorId, (int) $type);

    if (!$on) {
        db_query('DELETE FROM notification WHERE product_id = ? AND author_id = ? AND type = ?', $params);
        return;
    }

    $existing = db_one('SELECT id FROM notification WHERE product_id = ? AND author_id = ? AND type = ?', $params);
    if (!$existing) {
        notify($authorId, $objectId, $type);
    } elseif ($refresh) {
        /* $refresh (la cotisation a changé) : la notification remonte en tête, de nouveau non lue pour tous. */
        db_update('notification', array('created_at' => db_now()), array('id' => (int) $existing['id']));
        if (notification_states_enabled()) {
            db_query('DELETE FROM notification_state WHERE notification_id = ?', array((int) $existing['id']));
        }
    }
}

/* ---------- Les cadeaux que j'offre ---------- */

/**
 * Cadeaux prévus par un utilisateur (hors idées marquées « reçu »), regroupés par destinataire :
 * idées offertes seul, éléments de collection réservés, participations à un cadeau à plusieurs.
 * Retourne array(id du destinataire => array('owner' => …, 'gifts' => array(…))).
 */
function my_gifts($me)
{
    $notReceived = received_enabled() ? ' AND n.received_at IS NULL' : '';
    $rows = array();

    foreach (db_all('SELECT n.* FROM liste_noel n WHERE n.gifted_by = ?' . $notReceived, array((int) $me['id'])) as $object) {
        $rows[] = array('object' => $object, 'kind' => 'alone', 'detail' => '');
    }

    if (items_enabled()) {
        $items = db_all(
            'SELECT n.*, i.nom AS item_nom FROM liste_item i INNER JOIN liste_noel n ON n.id = i.product_id
                WHERE i.gifted_by = ?' . $notReceived . ' ORDER BY i.position ASC',
            array((int) $me['id'])
        );
        foreach ($items as $object) {
            $rows[] = array('object' => $object, 'kind' => 'item', 'detail' => $object['item_nom']);
        }
    }

    if (participations_enabled()) {
        $parts = db_all(
            'SELECT n.*, p.amount AS my_amount FROM liste_participation p INNER JOIN liste_noel n ON n.id = p.product_id
                WHERE p.user_id = ?' . $notReceived,
            array((int) $me['id'])
        );
        foreach ($parts as $object) {
            $rows[] = array('object' => $object, 'kind' => 'group', 'detail' => null !== $object['my_amount'] ? (float) $object['my_amount'] : null);
        }
    }

    $ownerIds = array();
    foreach ($rows as $row) {
        $ownerIds[] = $row['object']['user_id'];
    }
    $owners = users_by_ids($ownerIds);

    $grouped = array();
    foreach ($rows as $row) {
        $ownerId = (int) $row['object']['user_id'];
        if (!isset($owners[$ownerId])) {
            continue;
        }
        if (!isset($grouped[$ownerId])) {
            $grouped[$ownerId] = array('owner' => $owners[$ownerId], 'gifts' => array());
        }
        $row['object'] = object_prepare($row['object']);
        $grouped[$ownerId]['gifts'][] = $row;
    }

    return $grouped;
}

function my_gifts_count($grouped)
{
    if (!is_array($grouped)) {
        return 0;
    }

    $count = 0;
    foreach ($grouped as $group) {
        $count += count($group['gifts']);
    }

    return $count;
}

/* ---------- Notifications ---------- */

/**
 * Notifications visibles par un utilisateur :
 *   - toute activité des autres sur ses propres idées ;
 *   - les nouvelles idées de ses amis ;
 *   - les commentaires de ses amis sur les idées de ses amis ;
 *   - les dons de ses amis (réservations, participations) sur les listes de ses autres amis.
 * Les dons ne sont jamais montrés au propriétaire de la liste.
 * Retourne array(condition SQL, paramètres), pour la liste paginée et le compteur.
 */
function notifications_where($user, $friends)
{
    $friendIds = array();
    foreach ($friends as $friend) {
        $friendIds[] = (int) $friend['id'];
    }

    $userId = (int) $user['id'];
    $giftTypes = array(NOTIF_GIFT, NOTIF_PARTICIPATION);
    // Ses propres badges, puis les badges de ses amis (plus bas).
    $where = '((n.type = ? AND n.author_id = ?) OR n.author_id <> ?) AND ((n.type = ? AND n.author_id = ?) OR (p.user_id = ? AND n.type NOT IN (?))';
    $params = array(NOTIF_BADGE, $userId, $userId, NOTIF_BADGE, $userId, $userId, $giftTypes);
    // Parrainage : seulement pour le parrain du filleul.
    if (referral_enabled()) {
        $where .= ' OR (n.type = ? AND n.author_id IN (SELECT id FROM liste_user WHERE referred_by = ?))';
        array_push($params, NOTIF_REFERRAL, $userId);
    }

    if (0 < count($friendIds)) {
        $where .= ' OR (n.author_id IN (?) AND n.type = ?)';
        $where .= ' OR (n.author_id IN (?) AND n.type = ? AND p.user_id IN (?))';
        array_push($params, $friendIds, NOTIF_NEW_IDEA, $friendIds, NOTIF_COMMENT, $friendIds);
        $where .= ' OR (n.author_id IN (?) AND n.type = ?)';
        array_push($params, $friendIds, NOTIF_EVENT);
        $where .= ' OR (n.author_id IN (?) AND n.type = ?)';
        array_push($params, $friendIds, NOTIF_BADGE);
        $where .= ' OR (n.author_id IN (?) AND n.type IN (?) AND p.user_id IN (?) AND p.user_id <> ?)';
        array_push($params, $friendIds, $giftTypes, $friendIds, $userId);
        /* Cagnotte : visible de tous les amis du destinataire, même sans être ami avec le participant. */
        $where .= ' OR (n.type = ? AND p.user_id IN (?) AND p.user_id <> ?)';
        array_push($params, NOTIF_PARTICIPATION, $friendIds, $userId);
    }
    /* … et des autres participants de la même cagnotte. */
    if (participations_enabled()) {
        $where .= ' OR (n.type = ? AND p.user_id <> ? AND n.product_id IN (SELECT product_id FROM liste_participation WHERE user_id = ?))';
        array_push($params, NOTIF_PARTICIPATION, $userId, $userId);
    }

    $where .= ')';

    // Rien des listes privées des autres : ni leurs idées, ni leurs rappels d'événement.
    $hidden = hidden_list_ids($user);
    if (0 < count($hidden)) {
        $where .= ' AND (p.user_id IS NULL OR p.user_id NOT IN (?)) AND NOT (n.type IN (?) AND n.author_id IN (?))';
        array_push($params, $hidden, array(NOTIF_EVENT, NOTIF_BADGE), $hidden);
    }

    return array($where, $params);
}

define('NOTIFICATIONS_PER_PAGE', 10);

/**
 * Rappels d'événements : un mois, une semaine et la veille de l'événement d'un ami.
 * Créés à la volée par le premier ami qui passe sur le site, et partagés par tous les amis
 * (chacun garde son état lu / non lu). Seul le palier en cours est créé : pas de rafale de rappels en retard.
 */
function notifications_create_event_reminders($friends)
{
    $tiers = array(1, 7, 30);

    foreach ($friends as $friend) {
        $days = isset($friend['event_days']) ? $friend['event_days'] : event_days($friend);
        if (null === $days || $days < 1) {
            continue;
        }

        $tier = null;
        foreach ($tiers as $candidate) {
            if ($days <= $candidate) {
                $tier = $candidate;
                break;
            }
        }
        if (null === $tier) {
            continue;
        }

        // Déjà annoncé pour cet événement ? (le palier précédent est forcément plus ancien que 35 jours)
        $exists = db_one(
            'SELECT id FROM notification WHERE author_id = ? AND type = ? AND product_id = ? AND created_at >= ?',
            array((int) $friend['id'], NOTIF_EVENT, $tier, date('Y-m-d H:i:s', time() - 35 * 86400))
        );

        // Daté de sa création : il arrive en tête des notifications, non lu.
        if (!$exists) {
            db_insert('notification', array(
                'author_id' => (int) $friend['id'],
                'product_id' => $tier,
                'type' => NOTIF_EVENT,
                'created_at' => db_now(),
            ));
        }
    }
}

/**
 * La table notification_state existe-t-elle ? (migration sql/2026-10-01-notifications-lues.sql)
 */
function notification_states_enabled()
{
    return db_has_table('notification_state');
}

/**
 * Condition « non lue » : marquée non lue, ou plus récente que « Tout marquer comme lu » sans avoir été lue.
 * Retourne array(jointure, condition, paramètres de la jointure, paramètres de la condition).
 */
function notifications_unread_sql($user)
{
    $lastSeen = $user['last_seen_notif'] ? $user['last_seen_notif'] : '1970-01-01 00:00:00';

    if (!notification_states_enabled()) {
        return array('', 'n.created_at > ?', array(), array($lastSeen));
    }

    return array(
        'LEFT JOIN notification_state s ON s.notification_id = n.id AND s.user_id = ?',
        '(s.is_read = 0 OR (s.is_read IS NULL AND n.created_at > ?))',
        array((int) $user['id']),
        array($lastSeen),
    );
}

/**
 * Une page de notifications (les plus récentes d'abord). $unreadOnly : seulement les non lues.
 */
function notifications_for($user, $friends, $offset = 0, $limit = NOTIFICATIONS_PER_PAGE, $unreadOnly = false)
{
    list($where, $params) = notifications_where($user, $friends);
    list($join, $unread, $joinParams, $unreadParams) = notifications_unread_sql($user);

    if ($unreadOnly) {
        $where .= ' AND ' . $unread;
        $params = array_merge($params, $unreadParams);
    }

    $rows = db_all(
        'SELECT n.id, n.type, n.created_at, n.product_id,
                a.id AS author_id, a.nom AS author_nom, a.pictureFile AS author_pictureFile, a.pictureFileUrl AS author_pictureFileUrl,
                a.theme AS author_theme,
                p.user_id AS owner_id, COALESCE(o.code, a.code) AS owner_code, o.nom AS owner_nom, p.nom AS product_nom, r.type AS reaction_type,
                (' . $unread . ') AS is_unread
            FROM notification n
            INNER JOIN liste_user a ON a.id = n.author_id
            LEFT JOIN liste_noel p ON p.id = n.product_id AND n.type NOT IN (' . NOTIF_EVENT . ', ' . NOTIF_BADGE . ', ' . NOTIF_REFERRAL . ')
            LEFT JOIN liste_user o ON o.id = p.user_id
            LEFT JOIN reaction r ON n.type = ' . NOTIF_REACTION . ' AND r.product_id = n.product_id AND r.user_id = n.author_id
            ' . $join . '
            WHERE ' . $where . '
            ORDER BY n.created_at DESC, n.id DESC
            LIMIT ' . (int) $offset . ', ' . (int) $limit,
        array_merge($unreadParams, $joinParams, $params)
    );

    $notifications = array();

    foreach ($rows as $row) {
        $row['author'] = array(
            'id' => $row['author_id'],
            'nom' => $row['author_nom'],
            'pictureFile' => $row['author_pictureFile'],
            'pictureFileUrl' => $row['author_pictureFileUrl'],
            'theme' => $row['author_theme'],
        );
        $row['new'] = (bool) $row['is_unread'];
        $row['mine'] = (int) $row['owner_id'] === (int) $user['id'];
        $row['self'] = (int) $row['author_id'] === (int) $user['id'];
        $notifications[] = $row;
    }

    return notifications_add_badges(notifications_add_groups($notifications));
}

/**
 * Badges (NOTIF_BADGE) : le badge mis en avant et le nombre de badges du lot (même date d'obtention).
 * Ajoute 'badge' => array(emoji, name, kind, count) ; sans badge (désactivé, supprimé), la notification est ignorée.
 */
function notifications_add_badges($notifications)
{
    $ids = array();
    foreach ($notifications as $notification) {
        if (NOTIF_BADGE == $notification['type']) $ids[] = (int) $notification['product_id'];
    }
    if (0 === count($ids) || !badges_enabled()) {
        $kept = array();
        foreach ($notifications as $notification) {
            if (NOTIF_BADGE != $notification['type']) $kept[] = $notification;
        }
        return $kept;
    }

    $badges = db_index_by(db_all('SELECT id, emoji, name, kind FROM badge WHERE id IN (?) AND active = 1', array($ids)), 'id');

    // Nombre de badges par lot (personne + date d'obtention), en une requête.
    $authors = array();
    foreach ($notifications as $notification) {
        if (NOTIF_BADGE == $notification['type']) $authors[] = (int) $notification['author_id'];
    }
    $lots = array();
    foreach (db_all(
        'SELECT ub.user_id, ub.earned_at, COUNT(*) AS n FROM user_badge ub INNER JOIN badge b ON b.id = ub.badge_id
            WHERE ub.user_id IN (?) AND b.active = 1 GROUP BY ub.user_id, ub.earned_at',
        array($authors)
    ) as $row) {
        $lots[$row['user_id'] . '|' . $row['earned_at']] = (int) $row['n'];
    }

    $kept = array();
    foreach ($notifications as $notification) {
        if (NOTIF_BADGE == $notification['type']) {
            if (!isset($badges[$notification['product_id']])) continue;
            $key = $notification['author_id'] . '|' . $notification['created_at'];
            $notification['badge'] = $badges[$notification['product_id']];
            $notification['badge']['count'] = isset($lots[$key]) ? max(1, $lots[$key]) : 1;
        }
        $kept[] = $notification;
    }

    return $kept;
}

/**
 * Cagnottes (NOTIF_PARTICIPATION) : état actuel, chargé en trois requêtes pour toute la page.
 * Ajoute à chaque notification 'group' => array(count, total, price, started) ; started : l'auteur a lancé la cagnotte.
 */
function notifications_add_groups($notifications)
{
    $ids = array();
    foreach ($notifications as $notification) {
        if (NOTIF_PARTICIPATION == $notification['type']) $ids[] = (int) $notification['product_id'];
    }
    if (0 === count($ids) || !participations_enabled()) return $notifications;

    $groups = array();
    foreach (db_all('SELECT product_id, COUNT(*) AS count, SUM(amount) AS total FROM liste_participation WHERE product_id IN (?) GROUP BY product_id', array($ids)) as $row) {
        $groups[$row['product_id']] = array('count' => (int) $row['count'], 'total' => (float) $row['total'], 'price' => null, 'first' => 0);
    }
    /* Le premier arrivé a lancé la cagnotte. */
    foreach (db_all('SELECT product_id, user_id FROM liste_participation WHERE product_id IN (?) ORDER BY created_at DESC, user_id DESC', array($ids)) as $row) {
        if (isset($groups[$row['product_id']])) $groups[$row['product_id']]['first'] = (int) $row['user_id'];
    }
    if (prices_enabled()) {
        foreach (db_all('SELECT id, price FROM liste_noel WHERE id IN (?)', array($ids)) as $row) {
            if (isset($groups[$row['id']]) && null !== $row['price']) $groups[$row['id']]['price'] = (float) $row['price'];
        }
    }

    foreach ($notifications as $i => $notification) {
        $id = $notification['product_id'];
        if (NOTIF_PARTICIPATION == $notification['type'] && isset($groups[$id])) {
            $group = $groups[$id];
            $group['started'] = $group['first'] === (int) $notification['author_id'];
            $notifications[$i]['group'] = $group;
        }
    }

    return $notifications;
}

/**
 * Nombre de notifications non lues (pastille de la cloche).
 */
function notifications_new_count($user, $friends)
{
    list($where, $params) = notifications_where($user, $friends);
    list($join, $unread, $joinParams, $unreadParams) = notifications_unread_sql($user);

    $row = db_one(
        'SELECT COUNT(*) AS total
            FROM notification n
            LEFT JOIN liste_noel p ON p.id = n.product_id AND n.type NOT IN (' . NOTIF_EVENT . ', ' . NOTIF_BADGE . ', ' . NOTIF_REFERRAL . ')
            ' . $join . '
            WHERE ' . $where . ' AND ' . $unread,
        array_merge($joinParams, $params, $unreadParams)
    );

    return $row ? (int) $row['total'] : 0;
}

/**
 * Marque une notification comme lue ou non lue pour un utilisateur.
 */
function notification_set_read($user, $notificationId, $read)
{
    return db_query(
        'REPLACE INTO notification_state (user_id, notification_id, is_read) VALUES (?, ?, ?)',
        array((int) $user['id'], (int) $notificationId, $read ? 1 : 0)
    );
}

/**
 * « Tout marquer comme lu » : la date de référence avance, les états individuels deviennent inutiles.
 */
function notifications_mark_all_read($user)
{
    db_update('liste_user', array('last_seen_notif' => db_now()), array('id' => (int) $user['id']));

    if (notification_states_enabled()) {
        db_query('DELETE FROM notification_state WHERE user_id = ?', array((int) $user['id']));
    }
}
