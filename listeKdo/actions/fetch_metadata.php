<?php
/*
 * Récupère le titre, la description et l'image d'une page produit à partir de son lien.
 * La page est lue côté serveur : depuis le navigateur, la plupart des boutiques
 * bloquent la lecture (politique same-origin).
 */

require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
require_login();

$metadata = metadata_from_url(input('pageUrl'));

if ($metadata === false) {
    send_json(array('success' => false, 'message' => metadata_fetch_error_message()));
}

$metadata['success'] = true;
send_json($metadata);

function metadata_from_url($url)
{
    $GLOBALS['metadata_fetch_error'] = '';
    if ($url === '') {
        return false;
    }

    /* A pasted domain is a common use case. */
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'http://' . $url;
    }

    $page = metadata_fetch_page($url);
    if ($page === false && metadata_can_use_worker()) {
        $page = metadata_fetch_with_worker($url);
    }
    /*
     * Free hosting can fail before it receives the anti-bot response (for
     * example during TLS negotiation). In that case ScraperAPI is still the
     * useful fallback, so every failed direct request is retried there.
     */
    if ($page === false && metadata_can_use_scraper_api()) {
        $page = metadata_fetch_with_scraper_api($url);
    }
    if ($page === false || $page['html'] === '') {
        $title = metadata_title_from_url($url);
        if ($title !== '') {
            $message = 'Le site bloque ses informations : le nom a été repris depuis le lien.';
            if (isset($GLOBALS['metadata_fetch_error']) && $GLOBALS['metadata_fetch_error'] !== '') {
                $message .= ' (' . $GLOBALS['metadata_fetch_error'] . ')';
            }
            return array(
                'title' => $title,
                'description' => '',
                'image' => '',
                'partial' => true,
                'message' => $message
            );
        }
        return false;
    }

    return metadata_parse_page($page['html'], $page['url']);
}

function metadata_fetch_page($url)
{
    $redirects = 0;

    while ($redirects < 4) {
        if (!metadata_is_public_http_url($url)) {
            return false;
        }

        if (!function_exists('curl_init')) {
            $content = metadata_fetch_with_stream($url);
            return $content === false ? false : array('html' => $content, 'url' => $url);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 12);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8'
        ));

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        if ($response === false) {
            $GLOBALS['metadata_fetch_error'] = 'Impossible de joindre ce site pour le moment.';
            return false;
        }

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        if ($status >= 300 && $status < 400 && preg_match('/^Location:\s*(.+)$/im', $headers, $matches)) {
            $url = metadata_absolute_url(trim($matches[1]), $url);
            if ($url === false) {
                return false;
            }
            $redirects++;
            continue;
        }

        if ($status >= 200 && $status < 300) {
            if (metadata_is_bot_protection_page($body)) {
                $GLOBALS['metadata_fetch_error'] = 'Ce site bloque l’accès automatisé à ses informations (protection anti-robots).';
                return false;
            }
            return array('html' => substr($body, 0, 1048576), 'url' => $url);
        }

        if ($status === 401 || $status === 403) {
            $GLOBALS['metadata_fetch_error'] = 'Ce site bloque l’accès automatisé à ses informations (protection anti-robots).';
        }
        return false;
    }

    return false;
}

function metadata_fetch_with_stream($url)
{
    /* cURL is normally available on Free, this remains a simple fallback. */
    $context = stream_context_create(array('http' => array(
        'method' => 'GET',
        'timeout' => 12,
        'header' => "User-Agent: ListeKdo metadata bot/1.0\r\nAccept: text/html\r\n"
    )));
    $content = @file_get_contents($url, false, $context, 0, 1048576);

    return $content === false ? false : $content;
}

function metadata_can_use_scraper_api()
{
    return isset($GLOBALS['_scraperapi_enabled']) && $GLOBALS['_scraperapi_enabled']
        && isset($GLOBALS['_scraperapi_key']) && $GLOBALS['_scraperapi_key'] !== '';
}

function metadata_can_use_worker()
{
    return isset($GLOBALS['_metadata_worker_url']) && $GLOBALS['_metadata_worker_url'] !== ''
        && isset($GLOBALS['_metadata_worker_token']) && $GLOBALS['_metadata_worker_token'] !== '';
}

function metadata_fetch_with_worker($url)
{
    if (!function_exists('curl_init')) return false;

    $workerUrl = $GLOBALS['_metadata_worker_url'];
    $workerUrl .= (strpos($workerUrl, '?') === false ? '?' : '&') . 'url=' . urlencode($url);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $workerUrl);
    curl_setopt($curl, CURLOPT_PORT, 443);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $GLOBALS['_metadata_worker_token']));
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $content = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($content === false || $status < 200 || $status >= 300 || metadata_is_bot_protection_page($content)) {
        if ($content !== false && $status > 0) {
            $GLOBALS['metadata_fetch_error'] = 'Worker Cloudflare HTTP ' . $status;
        }
        return false;
    }

    $GLOBALS['metadata_fetch_error'] = '';
    return array('html' => substr($content, 0, 1048576), 'url' => $url);
}

function metadata_title_from_url($url)
{
    $parts = @parse_url($url);
    if ($parts === false || !isset($parts['path'])) return '';

    $segments = explode('/', trim($parts['path'], '/'));
    for ($i = count($segments) - 1; $i >= 0; $i--) {
        $segment = urldecode($segments[$i]);
        if ($segment === '' || preg_match('/^\d+$/', $segment)) continue;
        if (strtolower($segment) === 'p' || strtolower($segment) === 'product' || strtolower($segment) === 'products') continue;

        $title = preg_replace('/[-_]+/', ' ', $segment);
        $title = trim(preg_replace('/\s+/', ' ', $title));
        if ($title !== '') return $title;
    }

    return '';
}

function metadata_fetch_with_scraper_api($url)
{
    if (!function_exists('curl_init')) {
        return false;
    }

    /*
     * First use the standard endpoint from ScraperAPI's quick-start. This is
     * less expensive. Only retry with a rendered page and premium proxy when
     * the site still presents an anti-bot challenge.
     */
    $content = metadata_scraperapi_request($url, false);
    if ($content === false || metadata_is_bot_protection_page($content)) {
        $content = metadata_scraperapi_request($url, true);
    }

    if ($content === false || metadata_is_bot_protection_page($content)) {
        return false;
    }

    $GLOBALS['metadata_fetch_error'] = '';
    return array('html' => substr($content, 0, 1048576), 'url' => $url);
}

function metadata_scraperapi_request($url, $render)
{
    $apiUrl = 'https://api.scraperapi.com:443/?api_key=' . urlencode($GLOBALS['_scraperapi_key'])
        . '&url=' . urlencode($url);
    if ($render) {
        $apiUrl .= '&render=true&premium=true&country_code=fr';
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $apiUrl);
    /* Some very old cURL builds on Free incorrectly fall back to port 80. */
    curl_setopt($curl, CURLOPT_PORT, 443);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);
    curl_setopt($curl, CURLOPT_USERAGENT, 'ListeKdo metadata fetcher/1.0');
    /* Old Free cURL installations often have an obsolete CA bundle. */
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $content = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $errorNumber = curl_errno($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($content === false || $status < 200 || $status >= 300) {
        if ($content === false && $error !== '') {
            $GLOBALS['metadata_fetch_error'] = 'Le service de récupération externe est injoignable (cURL ' . $errorNumber . ' : ' . $error . ').';
        }
        return false;
    }

    return $content;
}

function metadata_is_bot_protection_page($html)
{
    return preg_match('/incapsula|request unsuccessful|captcha|access denied/i', $html) === 1;
}

function metadata_fetch_error_message()
{
    if (isset($GLOBALS['metadata_fetch_error']) && $GLOBALS['metadata_fetch_error'] !== '') {
        return $GLOBALS['metadata_fetch_error'];
    }
    return 'Impossible de lire les informations de cette page.';
}

function metadata_is_public_http_url($url)
{
    $parts = @parse_url($url);
    if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
        return false;
    }
    if (strtolower($parts['scheme']) !== 'http' && strtolower($parts['scheme']) !== 'https') {
        return false;
    }

    $host = strtolower($parts['host']);
    if ($host === 'localhost' || substr($host, -10) === '.localhost') {
        return false;
    }

    $ip = @gethostbyname($host);
    if ($ip === $host) {
        return false;
    }

    return !metadata_is_private_ip($ip);
}

function metadata_is_private_ip($ip)
{
    if (!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ip, $parts)) {
        return true; /* Do not fetch an IPv6 literal on this old fallback. */
    }

    $first = (int) $parts[1];
    $second = (int) $parts[2];
    if ($first === 10 || $first === 127 || $first === 0 || $first >= 224) return true;
    if ($first === 169 && $second === 254) return true;
    if ($first === 172 && $second >= 16 && $second <= 31) return true;
    if ($first === 192 && $second === 168) return true;

    return false;
}

function metadata_parse_page($html, $pageUrl)
{
    $values = array();
    preg_match_all('/<meta\b[^>]*>/is', $html, $tags);

    foreach ($tags[0] as $tag) {
        $attributes = metadata_attributes($tag);
        $name = '';
        if (isset($attributes['property'])) $name = strtolower($attributes['property']);
        if ($name === '' && isset($attributes['name'])) $name = strtolower($attributes['name']);
        if ($name !== '' && isset($attributes['content']) && !isset($values[$name])) {
            $values[$name] = metadata_clean_text($attributes['content']);
        }
    }

    $title = metadata_first_value($values, array('og:title', 'twitter:title', 'twitter:title:label'));
    if ($title === '' && preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $matches)) {
        $title = metadata_clean_text(strip_tags($matches[1]));
    }

    $description = metadata_first_value($values, array('og:description', 'twitter:description', 'description'));
    $image = metadata_first_value($values, array('og:image:secure_url', 'og:image:url', 'og:image', 'twitter:image:src', 'twitter:image'));

    if ($image === '' && preg_match('/<link\b[^>]*rel\s*=\s*["\']image_src["\'][^>]*>/i', $html, $matches)) {
        $attributes = metadata_attributes($matches[0]);
        if (isset($attributes['href'])) $image = metadata_clean_text($attributes['href']);
    }

    if ($image !== '') {
        $absoluteImage = metadata_absolute_url($image, $pageUrl);
        if ($absoluteImage !== false) $image = $absoluteImage;
    }

    return array('title' => $title, 'description' => $description, 'image' => $image);
}

function metadata_attributes($tag)
{
    $attributes = array();
    preg_match_all("/\\b([a-zA-Z_:][a-zA-Z0-9_:.\\-]*)\\s*=\\s*(?:\"([^\"]*)\"|'([^']*)'|([^\\s\"'=<>`]+))/", $tag, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $value = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : $match[4]);
        $attributes[strtolower($match[1])] = $value;
    }
    return $attributes;
}

function metadata_first_value($values, $keys)
{
    foreach ($keys as $key) {
        if (isset($values[$key]) && $values[$key] !== '') return $values[$key];
    }
    return '';
}

function metadata_clean_text($value)
{
    $value = html_entity_decode($value);
    $value = preg_replace('/\s+/', ' ', strip_tags($value));
    return trim($value);
}

function metadata_absolute_url($url, $baseUrl)
{
    if ($url === '') return false;
    if (preg_match('#^https?://#i', $url)) return $url;

    $base = @parse_url($baseUrl);
    if ($base === false || !isset($base['scheme']) || !isset($base['host'])) return false;
    if (substr($url, 0, 2) === '//') return $base['scheme'] . ':' . $url;

    $origin = $base['scheme'] . '://' . $base['host'];
    if (isset($base['port'])) $origin .= ':' . $base['port'];
    if (substr($url, 0, 1) === '/') return $origin . $url;

    $path = isset($base['path']) ? $base['path'] : '/';
    $path = preg_replace('#/[^/]*$#', '/', $path);
    return $origin . $path . $url;
}
