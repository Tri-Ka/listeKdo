<?php
/*
 * Point d'entrée commun à toutes les pages et actions.
 * Compatible PHP 4.4 (hébergement Free) : pas de syntaxe PHP 5+.
 */

define('KDO_ROOT', dirname(dirname(__FILE__)));
define('KDO_DEV', isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $_SERVER['HTTP_HOST']));

// En ligne, aucun message PHP ne doit s'afficher aux visiteurs.
// Chez Free, ini_set('display_errors') ne suffit pas : on coupe aussi le niveau de rapport.
ini_set('display_errors', KDO_DEV ? '1' : '0');
error_reporting(KDO_DEV ? E_ALL & ~E_NOTICE : 0);

kdo_unregister_globals();
kdo_strip_magic_quotes();

// config.php démarre la session et ouvre la connexion MySQL.
require_once KDO_ROOT . '/config.php';

// Avec register_globals, les variables de session deviennent des globales liées :
// on coupe ce lien pour qu'une variable locale ne puisse pas modifier la session.
kdo_unregister_session_globals();

require_once KDO_ROOT . '/lib/db.php';
require_once KDO_ROOT . '/lib/security.php';
require_once KDO_ROOT . '/lib/json.php';
require_once KDO_ROOT . '/lib/http.php';
require_once KDO_ROOT . '/lib/models.php';
require_once KDO_ROOT . '/lib/auth.php';
require_once KDO_ROOT . '/lib/view.php';
require_once KDO_ROOT . '/lib/upload.php';
require_once KDO_ROOT . '/lib/admin.php';
require_once KDO_ROOT . '/lib/badges.php';
require_once KDO_ROOT . '/lib/skins.php';

/**
 * Supprime les variables globales créées par register_globals à partir de la requête.
 */
function kdo_unregister_globals()
{
    if (!ini_get('register_globals')) {
        return;
    }

    $keep = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', '_SESSION');
    $sources = array($_GET, $_POST, $_COOKIE, $_FILES, $_REQUEST);

    foreach ($sources as $source) {
        foreach ($source as $key => $value) {
            if (!in_array($key, $keep)) {
                unset($GLOBALS[$key]);
            }
        }
    }
}

function kdo_unregister_session_globals()
{
    if (!ini_get('register_globals') || !isset($_SESSION)) {
        return;
    }

    foreach ($_SESSION as $key => $value) {
        if ($key !== 'GLOBALS' && $key !== '_SESSION') {
            unset($GLOBALS[$key]);
        }
    }
}

/**
 * magic_quotes_gpc est activé chez Free : on retire les antislashs ajoutés
 * automatiquement, l'échappement SQL est fait proprement dans db.php.
 */
function kdo_strip_magic_quotes()
{
    if (!get_magic_quotes_gpc()) {
        return;
    }

    $_GET = kdo_stripslashes_deep($_GET);
    $_POST = kdo_stripslashes_deep($_POST);
    $_COOKIE = kdo_stripslashes_deep($_COOKIE);
    $_REQUEST = kdo_stripslashes_deep($_REQUEST);
}

function kdo_stripslashes_deep($value)
{
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = kdo_stripslashes_deep($item);
        }

        return $value;
    }

    return stripslashes($value);
}
