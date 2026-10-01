<?php
/*
 * Réponses HTTP : les actions répondent en JSON aux appels fetch() du JS,
 * et par une redirection avec message flash quand le JS n'est pas disponible.
 */

function wants_json()
{
    return isset($_SERVER['HTTP_ACCEPT']) && false !== strpos($_SERVER['HTTP_ACCEPT'], 'application/json');
}

function request_is_post()
{
    return isset($_SERVER['REQUEST_METHOD']) && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']);
}

function input($key, $default = '')
{
    if (isset($_POST[$key])) {
        return is_array($_POST[$key]) ? $default : trim($_POST[$key]);
    }

    if (isset($_GET[$key])) {
        return is_array($_GET[$key]) ? $default : trim($_GET[$key]);
    }

    return $default;
}

function input_int($key)
{
    $value = input($key, '');

    return ctype_digit((string) $value) ? (int) $value : 0;
}

function send_json($data, $status = 200)
{
    if (200 !== $status) {
        header('Status: ' . $status);
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo kdo_json($data);
    exit;
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

/**
 * URL de la page d'une liste, relative au dossier actions/.
 */
function list_url($code = null, $anchor = '')
{
    $url = '../index.php';

    if (null !== $code && '' !== $code) {
        $url .= '?user=' . rawurlencode($code);
    }

    return $url . $anchor;
}

function flash($message, $type = 'error')
{
    $_SESSION['kdo_flash'] = array('type' => $type, 'message' => $message);
}

function flash_take()
{
    if (empty($_SESSION['kdo_flash'])) {
        return null;
    }

    $flash = $_SESSION['kdo_flash'];
    unset($_SESSION['kdo_flash']);

    return $flash;
}

/**
 * Termine une action en erreur.
 */
function fail($message, $redirectTo = '../index.php', $status = 400)
{
    if (wants_json()) {
        send_json(array('ok' => false, 'message' => $message), $status);
    }

    flash($message, 'error');
    redirect($redirectTo);
}

/**
 * Termine une action avec succès.
 */
function succeed($data = array(), $redirectTo = '../index.php', $message = '')
{
    if (wants_json()) {
        $data['ok'] = true;
        if ('' !== $message) {
            $data['message'] = $message;
        }
        send_json($data);
    }

    if ('' !== $message) {
        flash($message, 'success');
    }

    redirect($redirectTo);
}

/**
 * Garde commune des actions : POST + jeton CSRF valide.
 */
function require_post()
{
    if (!request_is_post()) {
        fail('Action non autorisée.', '../index.php', 405);
    }

    if (!csrf_valid()) {
        // Un formulaire plus gros que post_max_size (2 Mo chez Free) arrive vide.
        if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            fail('Le fichier envoyé est trop lourd (2 Mo maximum).', '../index.php', 413);
        }

        fail('Votre session a expiré, rechargez la page et réessayez.', '../index.php', 419);
    }
}

/**
 * Liste visée par une action (champ « owner » = code de la liste, sinon la sienne).
 * Refuse si l'utilisateur ne peut pas la gérer (ni la sienne, ni celle d'un enfant qu'il gère).
 */
function target_owner($me)
{
    $code = input('owner');
    $owner = '' !== $code ? user_find_by_code($code) : $me;

    if (!can_manage($me, $owner)) {
        fail("Vous ne pouvez pas modifier cette liste.", list_url($me['code']), 403);
    }

    return $owner;
}

/**
 * Idée d'une liste que l'utilisateur peut gérer, sinon arrêt.
 */
function managed_object($me, $id)
{
    $object = object_find($id);
    $owner = $object ? user_find($object['user_id']) : null;

    if (!$object || !can_manage($me, $owner)) {
        fail("Cette idée n'existe pas.", list_url($me['code']), 404);
    }

    return array($object, $owner);
}
