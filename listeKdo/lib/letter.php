<?php
/*
 * Envoi de la lettre imprimable (templates/print.php) en PDF par e-mail.
 * Le PDF est fabriqué par le navigateur (pages A4 en images), puis envoyé à actions/sendLetter.php.
 * Les photos des idées hébergées ailleurs passent par actions/printImage.php : le navigateur ne peut pas
 * les copier dans le PDF sans l'autorisation (CORS) de leur site.
 * Inclus seulement par ces deux actions.
 */

define('LETTER_MAX_RECIPIENTS', 10);
define('LETTER_DAILY_RECIPIENTS', 30);
define('LETTER_MAX_BYTES', 7340032); // 7 Mo : sous upload_max_filesize (8 Mo, docker/php8/php.ini).
define('LETTER_MESSAGE_MAX', 1000);
define('LETTER_IMAGE_MAX_BYTES', 4194304);

/**
 * Envoi possible : e-mails actifs (Freebox) et cURL pour les photos.
 */
function letter_mail_enabled()
{
    return mail_enabled();
}

/**
 * Adresses saisies (séparées par des virgules, points-virgules, espaces ou retours à la ligne) :
 * array('valid' => adresses uniques nettoyées, 'invalid' => saisies refusées).
 */
function letter_recipients($text)
{
    $valid = array();
    $invalid = array();

    foreach (preg_split('/[\s,;]+/', (string) $text) as $part) {
        $part = trim($part, " \t<>\"'");
        if ('' === $part) {
            continue;
        }
        $email = valid_email($part);
        if ($email) {
            $valid[$email] = $email;
        } else {
            $invalid[] = $part;
        }
    }

    return array('valid' => array_values($valid), 'invalid' => $invalid);
}

/* ---------- Limite d'envoi : LETTER_DAILY_RECIPIENTS destinataires par compte et par 24 h ---------- */

function letter_quota_file($user)
{
    $dir = KDO_ROOT . '/cache';
    if (!is_dir($dir) && @mkdir($dir, 0755)) {
        $htaccess = @fopen($dir . '/.htaccess', 'w');
        if ($htaccess) {
            fwrite($htaccess, "Deny from all\n");
            fclose($htaccess);
        }
    }

    return $dir . '/letters-' . (int) $user['id'] . '.txt';
}

/**
 * Dates des envois (un horodatage par destinataire) des dernières 24 h.
 */
function letter_quota_recent($user)
{
    $file = letter_quota_file($user);
    $recent = array();

    if (file_exists($file)) {
        foreach (file($file) as $line) {
            $time = (int) trim($line);
            if ($time > time() - 86400) {
                $recent[] = $time;
            }
        }
    }

    return $recent;
}

function letter_quota_left($user)
{
    return max(0, LETTER_DAILY_RECIPIENTS - count(letter_quota_recent($user)));
}

function letter_quota_add($user, $count)
{
    $lines = letter_quota_recent($user);
    for ($i = 0; $i < $count; $i++) {
        $lines[] = time();
    }

    $handle = @fopen(letter_quota_file($user), 'w');
    if ($handle) {
        fwrite($handle, implode("\n", $lines) . "\n");
        fclose($handle);
    }
}

/* ---------- Photos hébergées ailleurs ---------- */

/**
 * Adresse IPv4 publique : jamais le réseau local de la Freebox, la VM elle-même ou un service interne.
 */
function letter_public_ip($ip)
{
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $ip, $m)) {
        return false;
    }
    $a = (int) $m[1];
    $b = (int) $m[2];

    return !(0 === $a || 10 === $a || 127 === $a || (169 === $a && 254 === $b) || (172 === $a && 16 <= $b && $b <= 31)
        || (192 === $a && 168 === $b) || (100 === $a && 64 <= $b && $b <= 127) || 224 <= $a);
}

/**
 * Télécharge une image distante : http(s) seulement, hôtes publics seulement (vérifiés à chaque redirection),
 * LETTER_IMAGE_MAX_BYTES au plus. Retourne array('type' => …, 'data' => …) ou null.
 */
function letter_fetch_image($url)
{
    if (!function_exists('curl_init')) {
        return null;
    }

    for ($hop = 0; $hop < 4; $hop++) {
        if (!preg_match('#^https?://([^/:?\#]+)#i', $url, $match)) {
            return null;
        }
        $ip = gethostbyname($match[1]);
        if (!letter_public_ip($ip)) {
            return null;
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($curl, CURLOPT_TIMEOUT, 8);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (ListeKdo)');
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        if (false === $response) {
            return null;
        }
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        if (300 <= $status && $status < 400 && preg_match('/^Location:\s*(\S+)/mi', $headers, $location)) {
            $next = trim($location[1]);
            // Redirection relative : même site.
            if ('/' === substr($next, 0, 1)) {
                preg_match('#^(https?://[^/]+)#i', $url, $base);
                $next = $base[1] . $next;
            }
            $url = $next;
            continue;
        }

        if (200 !== $status || strlen($body) > LETTER_IMAGE_MAX_BYTES || !preg_match('/^Content-Type:\s*(image\/[a-z0-9.+-]+)/mi', $headers, $type)) {
            return null;
        }

        return array('type' => strtolower($type[1]), 'data' => $body);
    }

    return null;
}
