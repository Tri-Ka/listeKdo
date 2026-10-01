<?php
/*
 * Accès MySQL avec paramètres échappés.
 *
 * Les requêtes utilisent des marqueurs « ? » remplacés par des valeurs échappées :
 *   - entier / flottant  -> nombre brut
 *   - null               -> NULL
 *   - tableau            -> liste pour IN (...)
 *   - chaîne             -> 'valeur' échappée
 * Aucune valeur venant de l'utilisateur ne doit être concaténée directement dans le SQL.
 */

function db_quote($value)
{
    if (is_null($value)) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_array($value)) {
        if (0 === count($value)) {
            return 'NULL';
        }

        $items = array();
        foreach ($value as $item) {
            $items[] = db_quote($item);
        }

        return implode(', ', $items);
    }

    return "'" . mysql_real_escape_string((string) $value) . "'";
}

function db_build($sql, $params)
{
    if (0 === count($params)) {
        return $sql;
    }

    $parts = explode('?', $sql);
    $built = $parts[0];

    for ($i = 1; $i < count($parts); $i++) {
        $built .= db_quote(array_shift($params)) . $parts[$i];
    }

    return $built;
}

function db_query($sql, $params = array())
{
    $query = db_build($sql, $params);
    $result = mysql_query($query);

    if (false === $result) {
        error_log('listeKdo SQL error: ' . mysql_error() . ' -- ' . $query);

        if (KDO_DEV) {
            trigger_error('SQL : ' . mysql_error() . ' -- ' . $query, E_USER_WARNING);
        }
    }

    return $result;
}

function db_all($sql, $params = array())
{
    $result = db_query($sql, $params);
    $rows = array();

    if ($result) {
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function db_one($sql, $params = array())
{
    $result = db_query($sql, $params);

    if (!$result) {
        return null;
    }

    $row = mysql_fetch_assoc($result);

    return $row ? $row : null;
}

function db_insert($table, $values)
{
    $columns = array();
    $quoted = array();

    foreach ($values as $column => $value) {
        $columns[] = '`' . $column . '`';
        $quoted[] = db_quote($value);
    }

    $ok = db_query('INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $quoted) . ')');

    return $ok ? mysql_insert_id() : false;
}

/**
 * Met à jour les colonnes de $values pour la ligne $where (tableau colonne => valeur).
 */
function db_update($table, $values, $where)
{
    $sets = array();
    foreach ($values as $column => $value) {
        $sets[] = '`' . $column . '` = ' . db_quote($value);
    }

    $conditions = array();
    foreach ($where as $column => $value) {
        $conditions[] = '`' . $column . '` = ' . db_quote($value);
    }

    return db_query('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $conditions));
}

function db_now()
{
    return date('Y-m-d H:i:s');
}

/**
 * Index d'une liste de lignes par une colonne.
 */
function db_index_by($rows, $column)
{
    $indexed = array();
    foreach ($rows as $row) {
        $indexed[$row[$column]] = $row;
    }

    return $indexed;
}

/**
 * Convertit une liste de valeurs en entiers uniques (pour les IN).
 */
function db_ids($values)
{
    $ids = array();
    foreach ($values as $value) {
        if (null !== $value && '' !== $value) {
            $ids[(int) $value] = (int) $value;
        }
    }

    return array_values($ids);
}

/**
 * La table existe-t-elle ? (résultat gardé pour la durée de la requête)
 * Sert à masquer une fonctionnalité tant que sa migration (dossier sql/) n'a pas été faite.
 */
function db_has_table($table)
{
    static $cache = array();

    if (!isset($cache[$table])) {
        $cache[$table] = null !== db_one('SHOW TABLES LIKE ?', array($table));
    }

    return $cache[$table];
}

function db_has_column($table, $column)
{
    static $cache = array();
    $key = $table . '.' . $column;

    if (!isset($cache[$key])) {
        $cache[$key] = db_has_table($table) && null !== db_one('SHOW COLUMNS FROM `' . $table . '` LIKE ?', array($column));
    }

    return $cache[$key];
}
