<?php
/*
 * Échappement HTML, jetons CSRF, signatures et mots de passe.
 * PHP 4 n'a ni random_bytes, ni hash_hmac, ni password_hash : ils sont réimplémentés ici.
 */

/**
 * Échappe une valeur pour l'afficher dans du HTML (contenu ou attribut).
 */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function random_token()
{
    $entropy = uniqid(mt_rand(), true) . microtime() . mt_rand();

    $handle = @fopen('/dev/urandom', 'rb');
    if ($handle) {
        $entropy .= fread($handle, 32);
        fclose($handle);
    }

    return sha1($entropy);
}

function hmac_sha1($data, $key)
{
    if (strlen($key) > 64) {
        $key = pack('H*', sha1($key));
    }

    $key = str_pad($key, 64, chr(0));
    $inner = pack('H*', sha1(($key ^ str_repeat(chr(0x36), 64)) . $data));

    return sha1(($key ^ str_repeat(chr(0x5c), 64)) . $inner);
}

/**
 * Comparaison en temps constant, pour ne pas révéler la position de la première différence.
 */
function secure_equals($known, $given)
{
    if (strlen($known) !== strlen($given)) {
        return false;
    }

    $diff = 0;
    for ($i = 0; $i < strlen($known); $i++) {
        $diff |= ord($known[$i]) ^ ord($given[$i]);
    }

    return 0 === $diff;
}

/**
 * Clé secrète de l'application, dérivée de la configuration (jamais envoyée au navigateur).
 */
function app_secret()
{
    return sha1('listeKdo|' . $GLOBALS['_pass'] . '|' . $GLOBALS['_db'] . '|' . $GLOBALS['_username']);
}

/* ---------- CSRF ---------- */

function csrf_token()
{
    if (empty($_SESSION['kdo_csrf'])) {
        $_SESSION['kdo_csrf'] = random_token();
    }

    return $_SESSION['kdo_csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid()
{
    $given = '';

    if (isset($_POST['_token'])) {
        $given = $_POST['_token'];
    } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $given = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    return !empty($_SESSION['kdo_csrf']) && secure_equals($_SESSION['kdo_csrf'], (string) $given);
}

/* ---------- Mots de passe ---------- */

/*
 * Format stocké : s1$<sel>$<empreinte>, avec un SHA-1 itéré et salé.
 * Les anciens comptes ont un MD5 sans sel (32 caractères) : il est remplacé
 * par le nouveau format à la première connexion réussie.
 */
define('PASSWORD_ITERATIONS', 2000);

function password_make($password)
{
    $salt = substr(random_token(), 0, 16);

    return 's1$' . $salt . '$' . password_digest($password, $salt);
}

function password_digest($password, $salt)
{
    $hash = sha1($salt . $password);
    for ($i = 0; $i < PASSWORD_ITERATIONS; $i++) {
        $hash = sha1($hash . $salt . $password);
    }

    return $hash;
}

function password_matches($password, $stored)
{
    if ('' === (string) $stored || '' === (string) $password) {
        return false;
    }

    if ('s1$' === substr($stored, 0, 3)) {
        $parts = explode('$', $stored);

        return 3 === count($parts) && secure_equals($parts[2], password_digest($password, $parts[1]));
    }

    // Anciens comptes : MD5 calculé par l'ancien code, après l'échappement des magic quotes
    // (un mot de passe « l'ami » a été haché sous la forme « l\'ami »).
    return secure_equals($stored, md5($password)) || secure_equals($stored, md5(addslashes($password)));
}

function password_is_legacy($stored)
{
    return 's1$' !== substr($stored, 0, 3);
}

/* ---------- URL ---------- */

/**
 * Accepte uniquement les liens http(s), pour éviter les liens javascript: ou data:.
 */
function safe_url($url)
{
    $url = trim((string) $url);

    if ('' === $url) {
        return '';
    }

    if (!preg_match('#^https?://#i', $url)) {
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return '';
        }

        // Un domaine collé sans protocole (ex. « amazon.fr/... »).
        $url = 'https://' . ltrim($url, '/');
    }

    return $url;
}

/**
 * URL d'image : lien http(s) ou fichier du dossier uploads/.
 */
function safe_image_url($url)
{
    $url = trim((string) $url);

    if ('uploads/' === substr($url, 0, 8) && false === strpos($url, '..') && false === strpos($url, ':')) {
        return $url;
    }

    return safe_url($url);
}
