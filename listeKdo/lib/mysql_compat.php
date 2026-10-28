<?php
/*
 * PHP 7+ (serveur de la Freebox) : l'extension mysql_* n'existe plus.
 * Ces fonctions la remplacent par mysqli, pour garder le même code qu'en PHP 4 chez Free,
 * où mysql_connect() existe et rien n'est défini ici.
 * Chargé par lib/bootstrap.php avant config.php, qui ouvre la connexion.
 */
if (!function_exists('mysql_connect')) {
    function mysql_connect($host, $username, $password)
    {
        // Pas d'exceptions (défaut de PHP 8.1) : le code teste les valeurs de retour, comme en PHP 4.
        mysqli_report(MYSQLI_REPORT_OFF);
        $link = @mysqli_connect($host, $username, $password);
        if ($link) {
            // Comme le client mysql de PHP 4 : connexion en latin1, l'encodage dans lequel
            // les données existantes ont été écrites (voir « Encodage » dans CLAUDE.md).
            mysqli_set_charset($link, 'latin1');
        }
        $GLOBALS['kdo_mysqli'] = $link;

        return $link;
    }

    function mysql_compat_link()
    {
        return isset($GLOBALS['kdo_mysqli']) ? $GLOBALS['kdo_mysqli'] : false;
    }

    function mysql_select_db($database)
    {
        $link = mysql_compat_link();

        return $link ? mysqli_select_db($link, $database) : false;
    }

    function mysql_query($query)
    {
        $link = mysql_compat_link();

        return $link ? mysqli_query($link, $query) : false;
    }

    function mysql_error()
    {
        $link = mysql_compat_link();

        return $link ? mysqli_error($link) : (string) mysqli_connect_error();
    }

    function mysql_fetch_assoc($result)
    {
        if (!is_object($result)) {
            return false;
        }
        $row = mysqli_fetch_assoc($result);

        return $row ? $row : false;
    }

    function mysql_real_escape_string($value)
    {
        $link = mysql_compat_link();

        return $link ? mysqli_real_escape_string($link, $value) : addslashes($value);
    }

    function mysql_insert_id()
    {
        $link = mysql_compat_link();

        return $link ? mysqli_insert_id($link) : 0;
    }
}
