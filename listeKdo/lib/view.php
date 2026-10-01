<?php
/*
 * Aides pour les gabarits (templates/).
 */

/**
 * Chemin d'un fichier statique avec sa date de modification, pour invalider le cache navigateur.
 */
function asset($path)
{
    $file = KDO_ROOT . '/' . $path;

    return file_exists($file) ? $path . '?v=' . filemtime($file) : $path;
}

function icon($name, $class = '')
{
    return '<svg class="icon' . ('' !== $class ? ' ' . $class : '') . '" aria-hidden="true"><use href="' . e(asset('img/icons.svg')) . '#i-' . $name . '"></use></svg>';
}

function avatar_url($user)
{
    if (!$user) {
        return 'img/avatar-default.png';
    }

    if ('' !== (string) $user['pictureFile']) {
        return 'uploads/' . (int) $user['id'] . '/' . $user['pictureFile'];
    }

    if ('' !== (string) $user['pictureFileUrl']) {
        return safe_url($user['pictureFileUrl']);
    }

    return 'img/avatar-default.png';
}

function avatar($user, $class = 'avatar', $lazy = true, $tip = null)
{
    $name = $user ? $user['nom'] : '';

    // data-tip : le nom s'affiche au survol (infobulle gérée par js/app.js).
    return '<img class="' . e($class) . '" src="' . e(avatar_url($user)) . '" alt="' . e($name) . '"'
        . ('' !== $name ? ' data-tip="' . e(null !== $tip ? $tip : $name) . '"' : '')
        . ($lazy ? ' loading="lazy"' : '') . ' decoding="async" width="40" height="40" data-fallback="img/avatar-default.png">';
}

function theme_of($user)
{
    $themes = themes();
    $key = $user && isset($themes[$user['theme']]) ? $user['theme'] : 'noel';
    $theme = $themes[$key];
    $theme['key'] = $key;

    return $theme;
}

/**
 * Texte de thème avec le nom du propriétaire (« Anniversaire de Mallory »).
 */
function theme_text($theme, $field, $owner)
{
    return sprintf($theme[$field], $owner ? $owner['nom'] : 'vous');
}

function deco($theme, $name, $class = '')
{
    return '<img class="deco deco--' . e($name) . ('' !== $class ? ' ' . e($class) : '') . '" src="' . e(asset('img/deco/' . $theme['key'] . '/' . $name . '.png')) . '" alt="" decoding="async">';
}

/**
 * Coupe un texte UTF-8 sans casser un caractère accentué.
 */
function excerpt($text, $length)
{
    if (preg_match('/^.{' . (int) $length . '}/su', $text, $match) && strlen($match[0]) < strlen($text)) {
        return rtrim($match[0]) . '…';
    }

    return $text;
}

/**
 * Texte avec retours à la ligne et liens cliquables.
 * Tout est échappé ; seuls les liens <a href="…">…</a> des anciennes descriptions
 * et les adresses http(s) écrites en clair sont transformés en vrais liens.
 */
function multiline($text)
{
    $parts = preg_split(
        '#(<a\s[^>]*?href\s*=\s*["\']?[^"\'>\s]+["\']?[^>]*>.*?</a>|https?://[^\s<>"\']+[^\s<>"\'.,;:!?)\]])#is',
        (string) $text,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    $html = '';
    foreach ($parts as $i => $part) {
        if (0 === $i % 2) {
            $html .= nl2br(e($part));
        } elseif (preg_match('#^<a\s[^>]*?href\s*=\s*["\']?([^"\'>\s]+)["\']?[^>]*>(.*?)</a>$#is', $part, $match)) {
            $html .= text_link($match[1], trim(strip_tags($match[2])));
        } else {
            $html .= text_link($part, '');
        }
    }

    return $html;
}

function text_link($url, $label)
{
    $url = safe_url(html_entity_decode($url));

    if ('' === $label) {
        $label = short_url($url);
    }

    if ('' === $url) {
        return e($label);
    }

    return '<a href="' . e($url) . '" target="_blank" rel="noopener nofollow">' . e($label) . '</a>';
}

/**
 * « https://www.fnac.com/Appareil-Photo/a1636… » -> « fnac.com/Appareil-Photo… »
 */
function short_url($url)
{
    $short = preg_replace('#^https?://(www\.)?#i', '', $url);

    return strlen($short) > 40 ? substr($short, 0, 37) . '…' : $short;
}

/**
 * Texte sans balises de lien (pour les extraits des vignettes).
 */
function plain_text($text)
{
    return preg_replace('#<a\s[^>]*>(.*?)</a>#is', '$1', (string) $text);
}

function time_ago($datetime)
{
    $seconds = max(0, time() - strtotime($datetime));

    if ($seconds < 60) {
        return "à l'instant";
    }
    if ($seconds < 3600) {
        return floor($seconds / 60) . ' min';
    }
    if ($seconds < 86400) {
        return floor($seconds / 3600) . ' h';
    }
    if ($seconds < 86400 * 30) {
        return floor($seconds / 86400) . ' j';
    }

    return date('d/m/Y', strtotime($datetime));
}

function reaction_names($reactions)
{
    $names = array();
    foreach ($reactions as $reaction) {
        $names[] = $reaction['user']['nom'];
    }

    return implode(', ', $names);
}

/**
 * Affiche un gabarit avec des variables locales et renvoie le HTML produit.
 */
function render($template, $vars = array())
{
    extract($vars);
    ob_start();
    include KDO_ROOT . '/templates/' . $template . '.php';

    return ob_get_clean();
}

function share_url($user)
{
    return 'http://datcharrye.free.fr/listeKdo/?user=' . rawurlencode($user['code']);
}

/**
 * Lien court en HTTPS (TinyURL) qui redirige vers share_url().
 * Certaines applications (WhatsApp…) ouvrent les liens en HTTPS, que Free ne gère pas :
 * le lien court, lui, est en HTTPS puis redirige vers le site en HTTP.
 * Créé une seule fois par liste et gardé dans cache/ ; en cas d'échec, nouvel essai après un jour.
 */
function short_share_url($user)
{
    $long = share_url($user);
    $dir = KDO_ROOT . '/cache';
    $file = $dir . '/short-' . sha1($user['code']) . '.txt';

    if (file_exists($file)) {
        $cached = trim(implode('', file($file)));
        if ('' !== $cached) {
            return $cached;
        }
        if (filemtime($file) > time() - 86400) {
            return $long;
        }
    }

    $short = '';
    if (function_exists('curl_init')) {
        // API en HTTP simple : le SSL de PHP 4 chez Free est trop ancien pour certains sites.
        $curl = curl_init('http://tinyurl.com/api-create.php?url=' . rawurlencode($long));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);
        $response = trim((string) curl_exec($curl));
        curl_close($curl);

        if (preg_match('#^https://tinyurl\.com/[A-Za-z0-9-]+$#', $response)) {
            $short = $response;
        }
    }

    if (!is_dir($dir) && @mkdir($dir, 0755)) {
        $htaccess = @fopen($dir . '/.htaccess', 'w');
        if ($htaccess) {
            fwrite($htaccess, "Deny from all\n");
            fclose($htaccess);
        }
    }
    $handle = @fopen($file, 'w');
    if ($handle) {
        fwrite($handle, $short);
        fclose($handle);
    }

    return '' !== $short ? $short : $long;
}

/**
 * Élément de collection pour le formulaire de modification (sans les réservations).
 */
function item_for_form($item)
{
    return array('id' => (int) $item['id'], 'nom' => $item['nom']);
}

/**
 * 29.9 -> « 29,90 € » ; 30 -> « 30 € ».
 */
function format_price($amount)
{
    if (null === $amount) {
        return '';
    }

    $text = number_format((float) $amount, 2, ',', ' ');
    if (',00' === substr($text, -3)) {
        $text = substr($text, 0, -3);
    }

    return $text . ' €';
}

/**
 * Compte à rebours de la liste : « Anniversaire dans 12 jours », « C'est demain ! »…
 */
function event_label($user)
{
    $days = event_days($user);
    if (null === $days) {
        return '';
    }

    $names = array('birthday' => 'Anniversaire', 'noel' => 'Noël', 'naissance' => 'Naissance prévue');
    $name = isset($names[$user['theme']]) ? $names[$user['theme']] : 'Le grand jour';

    if (0 === $days) {
        return "C'est aujourd'hui !";
    }
    if (1 === $days) {
        return $name . ' demain !';
    }

    return $name . ' dans ' . $days . ' jours';
}

/**
 * Court : « dans 12 j », « demain », « aujourd'hui » (infobulles des amis).
 */
function event_short($user)
{
    $days = isset($user['event_days']) ? $user['event_days'] : event_days($user);
    if (null === $days) {
        return '';
    }
    if (0 === $days) {
        return "aujourd'hui";
    }
    if (1 === $days) {
        return 'demain';
    }

    return 'dans ' . $days . ' j';
}

/**
 * Date saisie (AAAA-MM-JJ, champ <input type="date">) -> date valide ou null.
 */
function valid_date($value)
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $value, $match) || !checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
        return null;
    }

    return $value;
}

/**
 * Nom de l'événement d'une liste : « Anniversaire », « Noël », « Naissance ».
 */
function event_name($user)
{
    $names = array('birthday' => 'Anniversaire', 'noel' => 'Noël', 'naissance' => 'Naissance');

    return isset($names[$user['theme']]) ? $names[$user['theme']] : 'Le grand jour';
}
