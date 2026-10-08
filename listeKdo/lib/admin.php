<?php
/*
 * Administration : rôles, tableaux paginés des utilisateurs et des listes, actions d'admin.
 * Nécessite la colonne liste_user.role (sql/2026-10-02-roles.sql) : sans elle, personne n'est admin.
 */

define('ADMIN_DEFAULT_PER_PAGE', 25);

function roles()
{
    return array(
        'user' => 'Utilisateur',
        'admin' => 'Administrateur',
    );
}

function roles_enabled()
{
    return db_has_column('liste_user', 'role');
}

function is_admin($user)
{
    return $user && roles_enabled() && isset($user['role']) && 'admin' === $user['role'];
}

/**
 * Garde des actions d'admin (après require_post()).
 */
function require_admin()
{
    $me = require_login();

    if (!is_admin($me)) {
        fail("Cette page est réservée aux administrateurs.", list_url($me['code']), 403);
    }

    return $me;
}

/**
 * Compte d'enfant : pas de mot de passe, géré par ses parents.
 */
function admin_is_child_account($user)
{
    return '' === (string) $user['password'];
}

/* ---------- Tableaux ---------- */

/**
 * Colonnes triables de chaque tableau : clé d'URL => expression SQL.
 */
function admin_sorts($tab)
{
    if ('lists' === $tab) {
        $sorts = array(
            'nom' => 'u.nom',
            'theme' => 'u.theme',
            'ideas' => 'ideas',
            'gifted' => 'gifted',
            'followers' => 'followers',
            'last_idea' => 'last_idea',
        );
        if (event_dates_enabled()) {
            $sorts['event'] = 'u.event_date';
        }

        return $sorts;
    }

    $sorts = array(
        'id' => 'u.id',
        'nom' => 'u.nom',
        'role' => 'u.role',
        'ideas' => 'ideas',
        'friends' => 'friends',
        'children' => 'children',
        'last_seen' => 'u.last_seen_at',
    );
    if (!last_seen_enabled()) {
        unset($sorts['last_seen']);
    }

    return $sorts;
}

function last_seen_enabled()
{
    return db_has_column('liste_user', 'last_seen_at');
}

/**
 * Filtres rapides (puces au-dessus du tableau) : clé => libellé.
 */
function admin_filters($tab)
{
    if ('lists' === $tab) {
        $filters = array('all' => 'Toutes', 'active' => 'Avec des idées', 'empty' => 'Vides');
        if (children_enabled()) {
            $filters['children'] = 'Secondaires';
        }

        return $filters;
    }

    $filters = array('all' => 'Tous', 'admin' => 'Admins', 'accounts' => 'Comptes');
    if (children_enabled()) {
        $filters['children'] = 'Secondaires';
    }

    return $filters;
}

/**
 * Paramètres du tableau lus dans l'URL, avec des valeurs autorisées uniquement.
 */
function admin_table_params()
{
    $tab = 'lists' === input('tab') ? 'lists' : 'users';
    $sorts = admin_sorts($tab);
    $filters = admin_filters($tab);

    $sort = input('sort');
    if (!isset($sorts[$sort])) {
        $sort = 'nom';
    }

    $filter = input('filter');
    if (!isset($filters[$filter])) {
        $filter = 'all';
    }

    $per = input_int('per');
    if (!in_array($per, array(10, 25, 50, 100))) {
        $per = ADMIN_DEFAULT_PER_PAGE;
    }

    return array(
        'tab' => $tab,
        'q' => substr(input('q'), 0, 100),
        'sort' => $sort,
        'dir' => 'desc' === input('dir') ? 'desc' : 'asc',
        'filter' => $filter,
        'page' => max(1, input_int('page')),
        'per' => $per,
    );
}

/**
 * URL de la page d'admin avec les paramètres du tableau, modifiés par $changes.
 */
function admin_url($params, $changes = array())
{
    foreach ($changes as $key => $value) {
        $params[$key] = $value;
    }

    $defaults = array('tab' => 'users', 'q' => '', 'sort' => 'nom', 'dir' => 'asc', 'filter' => 'all', 'page' => 1, 'per' => ADMIN_DEFAULT_PER_PAGE);
    $query = array();
    foreach ($defaults as $key => $default) {
        if (isset($params[$key]) && (string) $params[$key] !== (string) $default) {
            $query[] = $key . '=' . rawurlencode($params[$key]);
        }
    }

    return 'admin.php' . (0 < count($query) ? '?' . implode('&', $query) : '');
}

/**
 * Lien d'en-tête de colonne : trie par cette colonne, ou inverse le sens si elle est déjà triée.
 */
function admin_sort_url($params, $column)
{
    $dir = $params['sort'] === $column && 'asc' === $params['dir'] ? 'desc' : 'asc';

    return admin_url($params, array('sort' => $column, 'dir' => $dir, 'page' => 1));
}

function admin_like($text)
{
    return '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $text) . '%';
}

/**
 * Une page du tableau : array('rows' => …, 'total' => …, 'params' => … avec la page ramenée dans les bornes).
 */
function admin_table($params)
{
    $isLists = 'lists' === $params['tab'];
    $where = array('1 = 1');
    $args = array();

    if ('' !== $params['q']) {
        $where[] = '(u.nom LIKE ? OR u.code = ?)';
        $args[] = admin_like($params['q']);
        $args[] = $params['q'];
    }

    $childSql = children_enabled() ? 'EXISTS (SELECT 1 FROM liste_manager m WHERE m.child_id = u.id)' : '0';
    // Sans les suggestions des amis : un admin ne doit pas deviner celles faites sur sa propre liste.
    $ownSql = own_ideas_sql('n.');
    $ideasSql = '(SELECT COUNT(*) FROM liste_noel n WHERE n.user_id = u.id' . $ownSql . ')';

    switch ($params['filter']) {
        case 'admin':
            $where[] = roles_enabled() ? "u.role = 'admin'" : '0';
            break;
        case 'accounts':
            $where[] = 'NOT ' . $childSql;
            break;
        case 'children':
            $where[] = $childSql;
            break;
        case 'active':
            $where[] = $ideasSql . ' > 0';
            break;
        case 'empty':
            $where[] = $ideasSql . ' = 0';
            break;
    }

    $whereSql = implode(' AND ', $where);
    $count = db_one('SELECT COUNT(*) AS total FROM liste_user u WHERE ' . $whereSql, $args);
    $total = $count ? (int) $count['total'] : 0;

    $pages = max(1, (int) ceil($total / $params['per']));
    $params['page'] = min($params['page'], $pages);
    $params['pages'] = $pages;

    $columns = array('u.id', 'u.nom', 'u.code', 'u.password', 'u.theme', 'u.pictureFile', 'u.pictureFileUrl', $childSql . ' AS is_child', $ideasSql . ' AS ideas');
    $columns[] = roles_enabled() ? 'u.role' : "'user' AS role";

    if ($isLists) {
        $columns[] = '(SELECT COUNT(*) FROM liste_noel n WHERE n.user_id = u.id AND n.gifted_by IS NOT NULL' . $ownSql . ') AS gifted';
        $columns[] = '(SELECT COUNT(DISTINCT f.user_id) FROM user_friend f WHERE f.friend_code = u.code) AS followers';
        $columns[] = '(SELECT MAX(n.created_at) FROM liste_noel n WHERE n.user_id = u.id' . $ownSql . ') AS last_idea';
        $columns[] = received_enabled() ? '(SELECT COUNT(*) FROM liste_noel n WHERE n.user_id = u.id AND ' . received_objects_sql('n.') . $ownSql . ') AS received' : '0 AS received';
        $columns[] = event_dates_enabled() ? 'u.event_date' : 'NULL AS event_date';
        $columns[] = children_enabled()
            ? "(SELECT GROUP_CONCAT(p.nom ORDER BY p.nom SEPARATOR ', ') FROM liste_manager m INNER JOIN liste_user p ON p.id = m.user_id WHERE m.child_id = u.id) AS managers"
            : "'' AS managers";
    } else {
        $columns[] = '(SELECT COUNT(DISTINCT f.friend_code) FROM user_friend f WHERE f.user_id = u.id) AS friends';
        $columns[] = children_enabled() ? '(SELECT COUNT(*) FROM liste_manager m WHERE m.user_id = u.id) AS children' : '0 AS children';
        $columns[] = last_seen_enabled() ? 'u.last_seen_at' : 'NULL AS last_seen_at';
        $columns[] = secret_enabled() ? "(u.secret_question IS NOT NULL AND u.secret_question <> '') AS has_secret" : '0 AS has_secret';
    }

    $sorts = admin_sorts($params['tab']);
    $order = $sorts[$params['sort']] . ('desc' === $params['dir'] ? ' DESC' : ' ASC') . ', u.id ASC';
    $offset = ($params['page'] - 1) * $params['per'];

    $rows = db_all(
        'SELECT ' . implode(', ', $columns) . ' FROM liste_user u WHERE ' . $whereSql
            . ' ORDER BY ' . $order . ' LIMIT ' . (int) $offset . ', ' . (int) $params['per'],
        $args
    );

    return array('rows' => $rows, 'total' => $total, 'params' => $params);
}

/**
 * Chiffres clés en haut de la page.
 */
function admin_stats()
{
    $stats = array();
    $one = db_one('SELECT COUNT(*) AS n FROM liste_user');
    $stats['users'] = $one ? (int) $one['n'] : 0;

    $stats['children'] = 0;
    if (children_enabled()) {
        $one = db_one('SELECT COUNT(DISTINCT child_id) AS n FROM liste_manager');
        $stats['children'] = $one ? (int) $one['n'] : 0;
    }

    $one = db_one('SELECT COUNT(*) AS n, SUM(gifted_by IS NOT NULL) AS gifted FROM liste_noel');
    $stats['ideas'] = $one ? (int) $one['n'] : 0;
    $stats['gifted'] = $one ? (int) $one['gifted'] : 0;

    $stats['admins'] = 0;
    if (roles_enabled()) {
        $one = db_one("SELECT COUNT(*) AS n FROM liste_user WHERE role = 'admin'");
        $stats['admins'] = $one ? (int) $one['n'] : 0;
    }

    return $stats;
}

/**
 * Données agrégées de la vue d'ensemble. Les graphiques restent entièrement
 * côté serveur : pas de dépendance externe, ni de données personnelles envoyées
 * à un service tiers.
 */
function admin_insights()
{
    $insights = array(
        'activity' => array(),
        'ideas' => array('total' => 0, 'gifted' => 0, 'received' => 0),
        'counters' => array(),
        'badges' => array('enabled' => false, 'defined' => 0, 'earned' => 0, 'holders' => 0, 'all' => array()),
        'presence' => array('enabled' => false, 'now' => 0, 'day' => 0, 'week' => 0, 'month' => 0),
        'visits' => array('enabled' => false, 'days' => array()),
        'referrals' => array('enabled' => false, 'total' => 0, 'active' => 0),
        'gems' => array('enabled' => false, 'purchases' => 0, 'spent' => 0, 'skins' => array()),
    );

    // Les six derniers mois, mois courant compris. Une seule requête par série,
    // puis les mois sans activité sont ajoutés pour éviter les trous visuels.
    $months = array();
    $monthNames = array('jan.', 'fév.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
    $year = (int) date('Y');
    $month = (int) date('n');
    for ($i = 5; $i >= 0; $i--) {
        $stamp = mktime(0, 0, 0, $month - $i, 1, $year);
        $key = date('Y-m', $stamp);
        $months[$key] = array('key' => $key, 'label' => $monthNames[(int) date('n', $stamp) - 1], 'ideas' => 0, 'badges' => 0, 'spent' => 0);
    }

    foreach (db_all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS n FROM liste_noel WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key") as $row) {
        if (isset($months[$row['month_key']])) {
            $months[$row['month_key']]['ideas'] = (int) $row['n'];
        }
    }

    // Une idée reçue peut aussi avoir été réservée avant : les catégories du
    // graphique doivent rester exclusives pour former un anneau cohérent.
    $giftedSql = received_enabled()
        ? 'SUM(gifted_by IS NOT NULL AND NOT ' . received_objects_sql() . ') AS gifted'
        : 'SUM(gifted_by IS NOT NULL) AS gifted';
    $ideaRow = db_one('SELECT COUNT(*) AS total, ' . $giftedSql . (received_enabled() ? ', SUM(' . received_objects_sql() . ') AS received' : '') . ' FROM liste_noel');
    if ($ideaRow) {
        $insights['ideas']['total'] = (int) $ideaRow['total'];
        $insights['ideas']['gifted'] = (int) $ideaRow['gifted'];
        $insights['ideas']['received'] = received_enabled() ? (int) $ideaRow['received'] : 0;
    }

    $counterQueries = array(
        'friends' => array('Amis liés', 'user_friend'),
        'comments' => array('Commentaires', 'comment'),
        'reactions' => array('Réactions', 'reaction'),
    );
    if (db_has_column('liste_noel', 'favorite')) {
        $counterQueries['favorites'] = array('Coups de cœur', 'liste_noel WHERE favorite = 1');
    }
    if (items_enabled()) {
        $counterQueries['items'] = array('Éléments de collections', 'liste_item');
    }
    if (participations_enabled()) {
        $counterQueries['participations'] = array('Participations', 'liste_participation');
    }
    if (suggestions_enabled()) {
        $counterQueries['suggestions'] = array('Suggestions des amis', 'liste_noel WHERE suggested_by IS NOT NULL');
    }
    foreach ($counterQueries as $key => $info) {
        $row = db_one('SELECT COUNT(*) AS n FROM ' . $info[1]);
        $insights['counters'][] = array('key' => $key, 'label' => $info[0], 'value' => $row ? (int) $row['n'] : 0);
    }

    if (last_seen_enabled()) {
        $insights['presence']['enabled'] = true;
        $row = db_one('SELECT
            SUM(last_seen_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)) AS now_count,
            SUM(last_seen_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) AS day_count,
            SUM(last_seen_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS week_count,
            SUM(last_seen_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS month_count
            FROM liste_user');
        if ($row) {
            $insights['presence']['now'] = (int) $row['now_count'];
            $insights['presence']['day'] = (int) $row['day_count'];
            $insights['presence']['week'] = (int) $row['week_count'];
            $insights['presence']['month'] = (int) $row['month_count'];
        }
    }

    if (visit_history_enabled()) {
        $insights['visits']['enabled'] = true;
        $days = array();
        for ($i = 13; $i >= 0; $i--) {
            $stamp = strtotime('-' . $i . ' days');
            $key = date('Y-m-d', $stamp);
            $days[$key] = array('label' => date('d/m', $stamp), 'value' => 0);
        }
        foreach (db_all('SELECT visited_on, COUNT(*) AS n FROM user_visit WHERE visited_on >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY visited_on') as $row) {
            if (isset($days[$row['visited_on']])) $days[$row['visited_on']]['value'] = (int) $row['n'];
        }
        $insights['visits']['days'] = array_values($days);
    }

    if (badges_enabled()) {
        $insights['badges']['enabled'] = true;
        $row = db_one('SELECT COUNT(*) AS defined_count FROM badge');
        $insights['badges']['defined'] = $row ? (int) $row['defined_count'] : 0;
        $row = db_one('SELECT COUNT(*) AS earned_count, COUNT(DISTINCT user_id) AS holder_count FROM user_badge');
        $insights['badges']['earned'] = $row ? (int) $row['earned_count'] : 0;
        $insights['badges']['holders'] = $row ? (int) $row['holder_count'] : 0;
        foreach (db_all("SELECT b.name, b.emoji, COUNT(ub.user_id) AS holders FROM badge b LEFT JOIN user_badge ub ON ub.badge_id = b.id WHERE b.active = 1 GROUP BY b.id, b.name, b.emoji ORDER BY holders DESC, b.position ASC") as $row) {
            $insights['badges']['all'][] = array('name' => $row['name'], 'emoji' => $row['emoji'], 'holders' => (int) $row['holders']);
        }
        foreach (db_all("SELECT DATE_FORMAT(earned_at, '%Y-%m') AS month_key, COUNT(*) AS n FROM user_badge WHERE earned_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key") as $row) {
            if (isset($months[$row['month_key']])) $months[$row['month_key']]['badges'] = (int) $row['n'];
        }
    }

    if (referral_enabled()) {
        $insights['referrals']['enabled'] = true;
        $row = db_one('SELECT COUNT(*) AS total, SUM(EXISTS (SELECT 1 FROM liste_noel n WHERE n.user_id = u.id' . own_ideas_sql('n.') . ')) AS active FROM liste_user u WHERE referred_by IS NOT NULL');
        $insights['referrals']['total'] = $row ? (int) $row['total'] : 0;
        $insights['referrals']['active'] = $row ? (int) $row['active'] : 0;
    }

    if (skins_enabled()) {
        $insights['gems']['enabled'] = true;
        $row = db_one('SELECT COUNT(*) AS purchases, SUM(price) AS spent FROM user_skin');
        $insights['gems']['purchases'] = $row ? (int) $row['purchases'] : 0;
        $insights['gems']['spent'] = $row ? (int) $row['spent'] : 0;
        // Un article est un habillage pour un type de liste (ex. pastel/birthday).
        // On conserve le prix réellement payé : il peut avoir changé depuis l'achat.
        $items = skin_items();
        $themes = themes();
        foreach (db_all('SELECT skin, COUNT(*) AS purchases, SUM(price) AS spent FROM user_skin GROUP BY skin ORDER BY purchases DESC, skin ASC') as $row) {
            $key = (string) $row['skin'];
            if (isset($items[$key])) {
                $item = $items[$key];
                $label = $item['label'] . ' · ' . $themes[$item['theme']]['label'];
            } else {
                // Une ancienne entrée reste visible même si l'article a été retiré du catalogue.
                $label = $key;
            }
            $insights['gems']['skins'][] = array(
                'label' => $label,
                'purchases' => (int) $row['purchases'],
                'spent' => (int) $row['spent'],
            );
        }
        foreach (db_all("SELECT DATE_FORMAT(bought_at, '%Y-%m') AS month_key, SUM(price) AS n FROM user_skin WHERE bought_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key") as $row) {
            if (isset($months[$row['month_key']])) $months[$row['month_key']]['spent'] = (int) $row['n'];
        }
    }

    $insights['activity'] = array_values($months);

    return $insights;
}

/**
 * Pages à afficher dans la pagination : 1 … 4 5 [6] 7 8 … 20 (0 = points de suspension).
 */
function admin_page_numbers($page, $pages)
{
    $numbers = array();
    $last = 0;

    for ($i = 1; $i <= $pages; $i++) {
        if (1 === $i || $pages === $i || abs($i - $page) <= 1) {
            if ($i - $last > 1) {
                $numbers[] = 0;
            }
            $numbers[] = $i;
            $last = $i;
        }
    }

    return $numbers;
}

/* ---------- Actions ---------- */

function admin_set_role($user, $role)
{
    return db_update('liste_user', array('role' => $role), array('id' => (int) $user['id']));
}

/**
 * Nouveau mot de passe provisoire, facile à dicter (sans 0/O ni 1/l).
 * Changer le mot de passe révoque aussi les cookies « rester connecté » (signés avec lui).
 */
function admin_reset_password($user)
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
    $token = random_token();
    $password = '';
    for ($i = 0; $i < 10; $i++) {
        $password .= $alphabet[hexdec(substr($token, $i * 2, 2)) % strlen($alphabet)];
    }

    $changes = array('password' => password_make($password));
    if (reset_links_enabled()) {
        $changes['reset_token'] = null;
        $changes['reset_expires'] = null;
    }
    if (secret_enabled()) {
        $changes['secret_fails'] = 0;
        $changes['secret_locked_until'] = null;
    }

    return db_update('liste_user', $changes, array('id' => (int) $user['id'])) ? $password : false;
}

/**
 * Supprime un utilisateur et tout ce qui lui est lié : ses idées, ses amis, ses commentaires,
 * ses réactions, ses notifications et sa photo. Ses dons sur les listes des autres sont libérés.
 */
function admin_delete_user($user)
{
    $id = (int) $user['id'];

    foreach (db_all('SELECT * FROM liste_noel WHERE user_id = ?', array($id)) as $object) {
        object_delete($object);
    }

    db_query('DELETE FROM user_friend WHERE user_id = ? OR friend_code = ?', array($id, (string) $user['code']));
    db_query('DELETE FROM comment WHERE user_id = ?', array($id));
    db_query('DELETE FROM reaction WHERE user_id = ?', array($id));
    db_query('DELETE FROM notification WHERE author_id = ?', array($id));
    db_query('UPDATE liste_noel SET gifted_by = NULL WHERE gifted_by = ?', array($id));

    if (children_enabled()) {
        db_query('DELETE FROM liste_manager WHERE child_id = ? OR user_id = ?', array($id, $id));
    }
    if (viewers_enabled()) {
        db_query('DELETE FROM liste_viewer WHERE list_id = ? OR user_id = ?', array($id, $id));
    }
    if (badges_enabled()) {
        db_query('DELETE FROM user_badge WHERE user_id = ?', array($id));
    }
    if (notification_states_enabled()) {
        db_query('DELETE FROM notification_state WHERE user_id = ?', array($id));
    }
    if (participations_enabled()) {
        db_query('DELETE FROM liste_participation WHERE user_id = ?', array($id));
    }
    if (items_enabled()) {
        db_query('UPDATE liste_item SET gifted_by = NULL WHERE gifted_by = ?', array($id));
    }
    if (db_has_table('user_skin')) {
        db_query('DELETE FROM user_skin WHERE user_id = ?', array($id));
    }

    $ok = db_query('DELETE FROM liste_user WHERE id = ?', array($id));

    // Photo de profil : dossier uploads/<id>/.
    $directory = KDO_ROOT . '/uploads/' . $id;
    $handle = @opendir($directory);
    if ($handle) {
        while (false !== ($name = readdir($handle))) {
            if (is_file($directory . '/' . $name)) {
                @unlink($directory . '/' . $name);
            }
        }
        closedir($handle);
        @rmdir($directory);
    }

    upload_purge_orphan_images();

    return $ok;
}

/* ---------- Lien de secours (mot de passe oublié) ---------- */

define('RESET_LINK_HOURS', 48);

function reset_links_enabled()
{
    return db_has_column('liste_user', 'reset_token');
}

function site_base_url()
{
    if (KDO_DEV) {
        return 'http://' . $_SERVER['HTTP_HOST'] . '/listeKdo/';
    }

    return 'http://datcharrye.free.fr/listeKdo/';
}

/**
 * Crée un lien à usage unique pour choisir un nouveau mot de passe (remplace le précédent).
 * Seule l'empreinte du jeton est gardée en base. Retourne array('url', 'short', 'expires') ou false.
 */
function reset_link_create($user)
{
    $token = random_token();
    $expires = time() + RESET_LINK_HOURS * 3600;

    $ok = db_update('liste_user', array(
        'reset_token' => sha1($token),
        'reset_expires' => date('Y-m-d H:i:s', $expires),
    ), array('id' => (int) $user['id']));

    if (!$ok) {
        return false;
    }

    $url = site_base_url() . 'reset.php?t=' . (int) $user['id'] . '-' . $token;

    return array('url' => $url, 'short' => short_link($url), 'expires' => $expires);
}

/**
 * Utilisateur correspondant à un lien de secours valide (paramètre « id-jeton »), sinon null.
 */
function reset_link_user($param)
{
    if (!reset_links_enabled() || !preg_match('/^([0-9]+)-([0-9a-f]{40})$/', (string) $param, $match)) {
        return null;
    }

    $user = user_find((int) $match[1]);

    if (!$user || '' === (string) $user['reset_token'] || !secure_equals($user['reset_token'], sha1($match[2]))) {
        return null;
    }

    if (strtotime($user['reset_expires']) < time()) {
        return null;
    }

    return $user;
}

/**
 * Nouveau mot de passe choisi depuis le lien : le lien ne sert plus.
 */
function reset_link_use($user, $password)
{
    $changes = array('password' => password_make($password), 'reset_token' => null, 'reset_expires' => null);
    if (secret_enabled()) {
        $changes['secret_fails'] = 0;
        $changes['secret_locked_until'] = null;
    }

    return db_update('liste_user', $changes, array('id' => (int) $user['id']));
}

/**
 * Comptes qui peuvent être parents : avec un mot de passe, et pas eux-mêmes une liste d'enfant.
 */
function admin_parent_options()
{
    if (!children_enabled()) {
        return array();
    }

    return db_all("SELECT id, nom FROM liste_user WHERE password <> '' AND id NOT IN (SELECT child_id FROM liste_manager) ORDER BY nom ASC");
}

/* ---------- Affichage ---------- */

/**
 * En-tête de colonne triable.
 */
function admin_th($params, $column, $label, $class = '')
{
    $active = $params['sort'] === $column;
    $iconName = $active ? ('asc' === $params['dir'] ? 'sort-up' : 'sort-down') : 'sort';
    $aria = $active ? ' aria-sort="' . ('asc' === $params['dir'] ? 'ascending' : 'descending') . '"' : '';

    return '<th scope="col"' . ('' !== $class ? ' class="' . e($class) . '"' : '') . $aria . '>'
        . '<a class="dt__sort' . ($active ? ' is-active' : '') . '" href="' . e(admin_sort_url($params, $column)) . '" data-dt-link>'
        . e($label) . icon($iconName) . '</a></th>';
}

function admin_date($datetime)
{
    $time = '' !== (string) $datetime ? strtotime($datetime) : false;

    return $time && 0 < $time ? date('d/m/Y', $time) : '';
}

/**
 * « à l'instant », « il y a 5 min », « il y a 3 h », « hier », « il y a 12 j », puis la date.
 */
function admin_seen($datetime)
{
    $seconds = max(0, time() - strtotime($datetime));

    if ($seconds < 600) {
        return "À l'instant";
    }
    if ($seconds < 3600) {
        return 'Il y a ' . floor($seconds / 60) . ' min';
    }
    if ($seconds < 86400) {
        return 'Il y a ' . floor($seconds / 3600) . ' h';
    }
    if ($seconds < 2 * 86400) {
        return 'Hier';
    }
    if ($seconds < 30 * 86400) {
        return 'Il y a ' . floor($seconds / 86400) . ' j';
    }

    return admin_date($datetime);
}

/**
 * Badges pour l'administration, avec le nombre de personnes qui les ont obtenus.
 */
function admin_badges()
{
    $holders = array();
    foreach (db_all('SELECT badge_id, COUNT(*) AS n FROM user_badge GROUP BY badge_id') as $row) {
        $holders[(int) $row['badge_id']] = (int) $row['n'];
    }
    $badges = badges_all(false);
    foreach ($badges as $i => $badge) {
        $badges[$i]['holders'] = isset($holders[(int) $badge['id']]) ? $holders[(int) $badge['id']] : 0;
    }

    return $badges;
}
