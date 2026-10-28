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

/** Modules ES : chaque import utilise la date du fichier pour invalider son cache. */
function javascript_import_map()
{
    $imports = array();
    $files = glob(KDO_ROOT . '/js/modules/*.js');
    if (is_array($files)) {
        foreach ($files as $file) {
            $path = 'js/modules/' . basename($file);
            $imports['./' . $path] = './' . asset($path);
        }
    }

    return kdo_json(array('imports' => $imports));
}

/**
 * Capture d'écran de la page « Comment ça marche ? » (img/guide/), cliquable pour l'ouvrir en grand.
 * Largeur et hauteur en pixels CSS (les fichiers sont en double résolution).
 */
function guide_image($file, $alt, $width, $height, $class = '')
{
    $src = asset('img/guide/' . $file);

    return '<a class="guide-shot' . ('' !== $class ? ' ' . $class : '') . '" href="' . e($src) . '" target="_blank" rel="noopener">'
        . '<img src="' . e($src) . '" alt="' . e($alt) . '" width="' . (int) $width . '" height="' . (int) $height . '" loading="lazy" decoding="async"></a>';
}

function icon($name, $class = '')
{
    return '<svg class="icon' . ('' !== $class ? ' ' . $class : '') . '" aria-hidden="true"><use href="' . e(asset('img/icons.svg')) . '#i-' . $name . '"></use></svg>';
}

/**
 * Icône des gemmes (img/gem.png). $label : texte pour les lecteurs d'écran (vide = décorative).
 */
function gem_icon($class = '', $label = '')
{
    return '<img class="gem' . ('' !== $class ? ' ' . e($class) : '') . '" src="' . e(asset('img/gem.png')) . '" alt="' . e($label) . '" width="16" height="16"'
        . ('' === $label ? ' aria-hidden="true"' : '') . '>';
}

function avatar_url($user)
{
    if ($user && '' !== (string) $user['pictureFile']) {
        return 'uploads/' . (int) $user['id'] . '/' . $user['pictureFile'];
    }

    if ($user && '' !== (string) $user['pictureFileUrl']) {
        return safe_url($user['pictureFileUrl']);
    }

    return avatar_default_url($user);
}

/**
 * Avatar par défaut : l'initiale du prénom sur un dégradé, en SVG intégré (data: URI).
 * La couleur est tirée du prénom : toujours la même pour une personne.
 */
function avatar_default_url($user)
{
    $palettes = array(
        array('#ff9a8b', '#ff6a88'),
        array('#f6d365', '#fda085'),
        array('#84fab0', '#3cba92'),
        array('#8fd3f4', '#4a90e2'),
        array('#c3a6ff', '#7b5cf0'),
        array('#ffb6d9', '#e05297'),
        array('#ffd86f', '#fc6262'),
        array('#7ee8fa', '#38b2ac'),
        array('#fbc2eb', '#a18cd1'),
        array('#ffe29f', '#ffa99f'),
    );

    $name = $user ? trim((string) $user['nom']) : '';
    $initial = '?';
    if ('' !== $name && preg_match('/^./su', $name, $match)) {
        $initial = strtoupper($match[0]);
    }

    $colors = $palettes[abs(crc32(strtolower($name))) % count($palettes)];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="' . $colors[0] . '"/><stop offset="1" stop-color="' . $colors[1] . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="100" height="100" fill="url(#g)"/>'
        . '<circle cx="82" cy="18" r="10" fill="#fff" opacity=".25"/>'
        . '<circle cx="14" cy="88" r="16" fill="#fff" opacity=".15"/>'
        . '<text x="50" y="50" dy=".35em" text-anchor="middle" font-family="Outfit, system-ui, sans-serif" font-size="46" font-weight="800" fill="#fff">'
        . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</text></svg>';

    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}

function avatar($user, $class = 'avatar', $lazy = true, $tip = null)
{
    $name = $user ? $user['nom'] : '';

    // data-tip : le nom s'affiche au survol (infobulle gérée par js/modules/ui.js).
    // data-fallback : avatar à l'initiale si la photo envoyée est introuvable.
    $frame = accessory_worn($user, 'frame');
    $img = '<img class="' . e('' !== $frame ? 'mini-frame__img' : $class) . '" src="' . e(avatar_url($user)) . '" alt="' . e($name) . '"'
        . ('' !== $name ? ' data-tip="' . e(null !== $tip ? $tip : $name) . '"' : '')
        . ($lazy ? ' loading="lazy"' : '') . ' decoding="async" width="40" height="40" data-fallback="' . e(avatar_default_url($user)) . '">';

    if ('' === $frame) {
        return $img;
    }

    // Cadre acheté dans la boutique : le même que sur sa liste, réduit à la taille de la photo (css/app.css,
    // « Cadres », .mini-frame). L'enveloppe porte la classe de la photo, donc sa taille et sa place.
    $frames = frames();

    return '<span class="' . e($class) . ' mini-frame' . (!empty($frames[$frame]['image']) ? ' mini-frame--img' : '') . '" data-frame="' . e($frame) . '">'
        . $img . frame_html($frame) . '</span>';
}

function theme_of($user)
{
    $themes = themes();
    $key = $user && isset($themes[$user['theme']]) ? $user['theme'] : 'noel';
    $theme = $themes[$key];
    $theme['key'] = $key;

    // Habillage acheté dans la boutique (lib/skins.php) : titre, décorations et couleurs.
    return skin_apply($theme, $user);
}

/**
 * Texte de thème avec le nom du propriétaire (« Anniversaire de Mallory »).
 * Pour une liste secondaire, voir le commentaire de themes().
 */
function theme_text($theme, $field, $owner)
{
    static $secondary = array();

    // Titre choisi dans « Paramètres de la liste » ; sinon, titre par défaut du thème.
    if ($owner && 'heading' === $field && '' !== list_title($owner)) {
        return list_title($owner);
    }

    if ($owner) {
        $id = (int) $owner['id'];
        if (!isset($secondary[$id])) {
            $secondary[$id] = is_child_list($owner);
        }

        // Liste secondaire : son nom peut être un titre, on ne l'insère pas dans une phrase.
        if ($secondary[$id]) {
            if ('heading' === $field) {
                return $owner['nom'];
            }
            if (isset($theme[$field . '_list'])) {
                return $theme[$field . '_list'];
            }
        }
    }

    return sprintf($theme[$field], $owner ? $owner['nom'] : 'vous');
}

function deco($theme, $name, $class = '')
{
    return '<img class="deco deco--' . e($name) . ('' !== $class ? ' ' . e($class) : '') . '" src="' . e(asset('img/' . (isset($theme['dir']) ? $theme['dir'] : 'deco/' . $theme['key']) . '/' . $name . '.png')) . '" alt="" decoding="async">';
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

/**
 * Date d'un commentaire : « Aujourd'hui à 14:32 », « Hier à 09:10 », « 12 oct. à 14:32 », « 12 oct. 2025 à 14:32 ».
 */
function comment_date($datetime)
{
    $time = '' !== (string) $datetime ? strtotime($datetime) : false;
    if (!$time || 0 >= $time) {
        return '';
    }

    $months = array('janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
    $day = date('Y-m-d', $time);
    $hour = ' à ' . date('H:i', $time);

    if (date('Y-m-d') === $day) {
        return "Aujourd'hui" . $hour;
    }
    if (date('Y-m-d', strtotime('-1 day')) === $day) {
        return 'Hier' . $hour;
    }

    $label = date('j', $time) . ' ' . $months[date('n', $time) - 1];
    if (date('Y') !== date('Y', $time)) {
        $label .= ' ' . date('Y', $time);
    }

    return $label . $hour;
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
    return KDO_SITE_URL . '?user=' . rawurlencode($user['code']);
}

/**
 * Lien court en HTTPS d'un lien du site : partage d'une liste, parrainage, invitation, lien de secours…
 * Certaines applications (WhatsApp…) ouvrent les liens en HTTPS, que Free ne gère pas :
 * le lien court, lui, est en HTTPS puis redirige vers le site en HTTP.
 * Les services serveur gardent leurs liens dans cache/ ; en cas d'échec, reprise après cinq minutes.
 * Le mode automatique récupère les anciens liens ou délègue la création à da.gd dans le navigateur.
 * Seuls les liens du site en ligne sont raccourcis : en local (localhost), le lien long est rendu tel quel.
 * Trop lent pour l'affichage : les pages appellent actions/shortUrl.php en arrière-plan (js/modules/sharing.js, [data-short]).
 */
function short_link($long)
{
    if (0 !== strpos($long, KDO_SITE_URL)) {
        return $long;
    }

    $provider = short_link_provider();
    if ('none' === $provider) return $long;
    $dir = KDO_ROOT . '/cache';
    $prefix = 'tinyurl' === $provider ? 'short-' : 'short-' . $provider . '-';
    $file = $dir . '/' . $prefix . sha1($long) . '.txt';
    $retry = $dir . '/' . $prefix . sha1($long) . '.retry';

    $files = array($file);
    if ('auto' === $provider) {
        $files[] = $dir . '/short-' . sha1($long) . '.txt';
        foreach (short_link_services() as $key => $service) {
            if ('tinyurl' !== $key) $files[] = $dir . '/short-' . $key . '-' . sha1($long) . '.txt';
        }
    }
    // Avant la généralisation aux invitations et au parrainage, le cache des listes
    // utilisait sha1(code) : garder ces liens, car Free peut ne plus joindre TinyURL.
    if (in_array($provider, array('auto', 'tinyurl')) && preg_match('#^http://datcharrye\.free\.fr/listeKdo/\?user=([a-f0-9]{32})$#D', $long, $matches)) {
        $files[] = $dir . '/short-' . sha1($matches[1]) . '.txt';
    }
    foreach ($files as $cachedFile) {
        if (file_exists($cachedFile)) {
            $cached = short_link_normalize(implode('', file($cachedFile)), 'auto' === $provider ? '' : $provider);
            if ('' !== $cached) {
                return $cached;
            }
        }
    }
    // da.gd accepte CORS : le navigateur contourne les sorties bloquées de Free.
    if (in_array($provider, array('auto', 'dagd'))) return $long;
    // Les anciens fichiers .txt vides ne doivent plus bloquer un lien pendant 24 h.
    if (file_exists($retry) && filemtime($retry) > time() - 300) {
        return $long;
    }

    $result = short_link_request($provider, $long);
    $short = $result['url'];

    if (!is_dir($dir) && @mkdir($dir, 0755)) {
        $htaccess = @fopen($dir . '/.htaccess', 'w');
        if ($htaccess) {
            fwrite($htaccess, "Deny from all\n");
            fclose($htaccess);
        }
    }
    $handle = @fopen('' !== $short ? $file : $retry, 'w');
    if ($handle) {
        fwrite($handle, $short);
        fclose($handle);
    }
    if ('' !== $short && file_exists($retry)) {
        @unlink($retry);
    }

    return '' !== $short ? $short : $long;
}

/**
 * Accepte uniquement les domaines des services proposés, toujours partagés en HTTPS.
 */
function short_link_normalize($response, $provider = '')
{
    $response = trim((string) $response);
    if (preg_match('#^https?://(tinyurl\.com|is\.gd|v\.gd|da\.gd)/([A-Za-z0-9_-]+)$#D', $response, $matches)) {
        $services = short_link_services();
        if ('' !== $provider && (!isset($services[$provider]) || $services[$provider]['domain'] !== $matches[1])) return '';
        return 'https://' . $matches[1] . '/' . $matches[2];
    }

    return '';
}

function short_link_services()
{
    return array(
        'tinyurl' => array('label' => 'TinyURL', 'domain' => 'tinyurl.com', 'api' => 'http://tinyurl.com/api-create.php?url='),
        'isgd' => array('label' => 'is.gd', 'domain' => 'is.gd', 'api' => 'https://is.gd/create.php?format=simple&url='),
        'vgd' => array('label' => 'v.gd', 'domain' => 'v.gd', 'api' => 'https://v.gd/create.php?format=simple&url='),
        'dagd' => array('label' => 'da.gd', 'domain' => 'da.gd', 'api' => 'https://da.gd/s?url='),
    );
}

function short_link_options()
{
    return array(
        'auto' => 'Automatique (recommandé)',
        'dagd' => 'da.gd — depuis le navigateur',
        'tinyurl' => 'TinyURL — depuis le serveur',
        'isgd' => 'is.gd — depuis le serveur',
        'vgd' => 'v.gd — depuis le serveur',
        'none' => 'Désactivé — lien complet',
    );
}

function short_link_provider()
{
    $provider = setting('short_link_provider', 'auto');
    $options = short_link_options();
    return isset($options[$provider]) ? $provider : 'auto';
}

function short_link_browser($long, $provider = null)
{
    if (null === $provider) $provider = short_link_provider();
    if (0 !== strpos($long, KDO_SITE_URL) || !in_array($provider, array('auto', 'dagd'))) return null;
    $services = short_link_services();
    return array('provider' => 'dagd', 'endpoint' => $services['dagd']['api'], 'site' => KDO_SITE_URL);
}

/**
 * Test sans cache d'un service côté serveur. Les adresses sont limitées au site.
 */
function short_link_request($provider, $long)
{
    $failed = array('url' => '', 'message' => 'Ce service ne répond pas depuis le serveur. Le lien complet reste disponible.');
    $services = short_link_services();
    if (!isset($services[$provider]) || 0 !== strpos($long, KDO_SITE_URL) || !function_exists('curl_init')) return $failed;
    $curl = curl_init($services[$provider]['api'] . rawurlencode($long));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($curl, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $short = short_link_normalize($response, $provider);
    if ($status < 200 || $status >= 300 || '' === $short) return $failed;
    return array('url' => $short, 'message' => 'Le service fonctionne depuis le serveur.');
}

/**
 * Lien de parrainage d'un compte (ouvre l'inscription avec le code prérempli).
 */
function referral_url($user)
{
    return site_base_url() . '?parrain=' . referral_code($user);
}

/**
 * Élément de collection pour le formulaire de modification (sans les réservations).
 */
function item_for_form($item)
{
    return array('id' => (int) $item['id'], 'nom' => $item['nom'], 'link' => isset($item['link']) ? safe_url($item['link']) : '');
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

    $theme = theme_of($user);
    $name = $theme['soon'];

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
 * Nom de l'événement d'une liste : « Anniversaire », « Noël », « Naissance », « Mariage ».
 */
function event_name($user)
{
    $theme = theme_of($user);

    return $theme['event'];
}
