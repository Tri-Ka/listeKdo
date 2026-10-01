<?php
/*
 * Authentification.
 *
 * La session ne contient que l'id de l'utilisateur. Le cookie « rester connecté »
 * est signé (HMAC) et lié au mot de passe : changer de mot de passe le révoque.
 * L'ancien cookie listeKdoUserCode contenait le code public de la liste, visible
 * dans les liens de partage : il permettait de se connecter à la place de quelqu'un.
 * Il est supprimé et n'est plus lu.
 */

define('AUTH_COOKIE', 'listeKdoAuth');
define('AUTH_COOKIE_DAYS', 365);

function current_user()
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (!empty($_SESSION['kdo_user_id'])) {
        $user = user_find($_SESSION['kdo_user_id']);
    }

    if (!$user && isset($_COOKIE[AUTH_COOKIE])) {
        $user = auth_user_from_cookie($_COOKIE[AUTH_COOKIE]);

        if ($user) {
            $_SESSION['kdo_user_id'] = (int) $user['id'];
        } else {
            auth_set_cookie('', time() - 3600);
        }
    }

    if (!$user) {
        unset($_SESSION['kdo_user_id']);
    }

    return $user;
}

function is_logged_in()
{
    return null !== current_user();
}

function require_login()
{
    $user = current_user();

    if (!$user) {
        fail('Connectez-vous pour faire ça.', '../index.php', 401);
    }

    return $user;
}

function login($user)
{
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id();
    }

    $_SESSION['kdo_user_id'] = (int) $user['id'];
    $expires = time() + AUTH_COOKIE_DAYS * 86400;
    auth_set_cookie(auth_cookie_value($user, $expires), $expires);
}

function logout()
{
    $_SESSION = array();
    auth_set_cookie('', time() - 3600);
    session_destroy();
}

function auth_cookie_value($user, $expires)
{
    $payload = $user['id'] . '.' . $expires;

    return $payload . '.' . hmac_sha1($payload . '.' . $user['password'], app_secret());
}

function auth_user_from_cookie($value)
{
    $parts = explode('.', (string) $value);

    if (3 !== count($parts) || !ctype_digit($parts[0]) || !ctype_digit($parts[1]) || (int) $parts[1] < time()) {
        return null;
    }

    $user = user_find((int) $parts[0]);

    if (!$user || !secure_equals(auth_cookie_value($user, $parts[1]), (string) $value)) {
        return null;
    }

    return $user;
}

/**
 * setcookie() de PHP 4 ne gère pas HttpOnly : l'en-tête est écrit à la main.
 */
function auth_set_cookie($value, $expires)
{
    $cookie = AUTH_COOKIE . '=' . rawurlencode($value)
        . '; expires=' . gmdate('D, d-M-Y H:i:s', $expires) . ' GMT'
        . '; path=/; HttpOnly; SameSite=Lax';

    header('Set-Cookie: ' . $cookie, false);
}

/**
 * Nettoie les cookies de l'ancienne version du site.
 */
function auth_forget_legacy_cookies()
{
    if (isset($_COOKIE['listeKdoUserCode'])) {
        setcookie('listeKdoUserCode', '', time() - 3600, '/');
    }

    if (isset($_COOKIE['listKdoGoogleAuth'])) {
        setcookie('listKdoGoogleAuth', '', time() - 3600, '/');
        setcookie('listKdoGoogleAuth', '', time() - 3600, '/listeKdo');
    }

    // Anciennes clés de session (utilisateur complet stocké en session).
    unset($_SESSION['user'], $_SESSION['error'], $_SESSION['currentUserCode']);
}

auth_forget_legacy_cookies();
