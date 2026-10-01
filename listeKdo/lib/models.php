<?php
/*
 * Accès aux données. Tables historiques (noms conservés) :
 *   liste_user    utilisateurs (code = identifiant public de la liste, utilisé dans les liens)
 *   liste_noel    idées cadeaux (« objets »)
 *   comment       commentaires sur une idée
 *   reaction      réactions (type 1 à 6) sur une idée
 *   notification  type 1 = commentaire, 2 = nouvelle idée, 3 = réaction
 *   user_friend   amitiés (user_id -> friend_code)
 */

define('NOTIF_COMMENT', 1);
define('NOTIF_NEW_IDEA', 2);
define('NOTIF_REACTION', 3);
// Rappel d'événement d'un ami. author_id = l'ami, product_id = palier en jours (30, 7 ou 1), pas une idée.
define('NOTIF_EVENT', 4);

/**
 * Thèmes de liste. %s est remplacé par le nom du propriétaire.
 * Les décorations sont dans img/deco/<thème>/.
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
            'left' => array('gifts', 'hat'),
            'right' => array('balloons', 'bunting'),
            'footer' => 'cake',
        ),
        'noel' => array(
            'label' => 'Noël',
            'title' => 'Liste de Noël',
            'heading' => 'Noël de %s',
            'subtitle' => 'Les idées cadeaux de %s pour un Noël magique 🎄',
            'note' => 'Aidez le Père Noël à gâter %s !',
            'left' => array('holly', 'ornament', 'candy'),
            'right' => array('scene', 'snowflake'),
            'footer' => 'holly',
        ),
        'naissance' => array(
            'label' => 'Naissance',
            'title' => 'Liste de naissance',
            'heading' => 'Naissance chez %s',
            'subtitle' => "Une liste toute douce pour accueillir bébé 💙",
            'note' => "Merci de préparer avec nous l'arrivée de bébé !",
            'left' => array('moon', 'cloud', 'bottle'),
            'right' => array('mobile', 'teddy', 'heart'),
            'footer' => 'baby',
        ),
    );
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

function user_create($name, $password, $pictureFile)
{
    return db_insert('liste_user', array(
        'nom' => $name,
        'code' => random_token(),
        'password' => password_make($password),
        'theme' => 'noel',
        'pictureFile' => $pictureFile,
    ));
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
 *   - naissance : la date de naissance prévue (date unique ; passée, plus de compte à rebours).
 */
function event_next($user)
{
    $theme = isset($user['theme']) ? $user['theme'] : 'noel';
    $today = mktime(0, 0, 0, (int) date('n'), (int) date('j'), (int) date('Y'));

    if ('noel' === $theme) {
        $month = 12;
        $day = 25;
    } else {
        $date = birth_date($user);
        if (null === $date) {
            return null;
        }
        list($year, $month, $day) = explode('-', $date);

        if ('naissance' === $theme) {
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

    db_query('DELETE FROM notification WHERE product_id = ? AND type <> ?', array($id, NOTIF_EVENT));
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
        'REPLACE INTO liste_participation (product_id, user_id, amount, created_at) VALUES (?, ?, ?, ?)',
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
 *   - les commentaires de ses amis sur les idées de ses amis.
 * Retourne array(condition SQL, paramètres), pour la liste paginée et le compteur.
 */
function notifications_where($user, $friends)
{
    $friendIds = array();
    foreach ($friends as $friend) {
        $friendIds[] = (int) $friend['id'];
    }

    $userId = (int) $user['id'];
    $where = 'n.author_id <> ? AND (p.user_id = ?';
    $params = array($userId, $userId);

    if (0 < count($friendIds)) {
        $where .= ' OR (n.author_id IN (?) AND n.type = ?)';
        $where .= ' OR (n.author_id IN (?) AND n.type = ? AND p.user_id IN (?))';
        array_push($params, $friendIds, NOTIF_NEW_IDEA, $friendIds, NOTIF_COMMENT, $friendIds);
        $where .= ' OR (n.author_id IN (?) AND n.type = ?)';
        array_push($params, $friendIds, NOTIF_EVENT);
    }

    return array($where . ')', $params);
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
                p.user_id AS owner_id, COALESCE(o.code, a.code) AS owner_code,
                (' . $unread . ') AS is_unread
            FROM notification n
            INNER JOIN liste_user a ON a.id = n.author_id
            LEFT JOIN liste_noel p ON p.id = n.product_id AND n.type <> ' . NOTIF_EVENT . '
            LEFT JOIN liste_user o ON o.id = p.user_id
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
        $notifications[] = $row;
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
            LEFT JOIN liste_noel p ON p.id = n.product_id AND n.type <> ' . NOTIF_EVENT . '
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
