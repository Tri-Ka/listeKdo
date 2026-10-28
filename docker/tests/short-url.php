<?php
/*
 * Régression PHP 4 du cache TinyURL, sans accès à la base.
 * --live ajoute une vérification réelle du raccourcissement et des anciens caches vides.
 */
$out = dirname(__FILE__) . '/out';
if (!is_dir($out)) mkdir($out, 0755);
define('KDO_ROOT', $out . '/short-url-' . uniqid(''));
define('KDO_SITE_URL', 'http://datcharrye.free.fr/listeKdo/');
require_once dirname(__FILE__) . '/../../listeKdo/lib/view.php';
$failures = 0;
$GLOBALS['short_test_provider'] = 'tinyurl';

function setting($name, $default)
{
    return isset($GLOBALS['short_test_provider']) ? $GLOBALS['short_test_provider'] : $default;
}

function short_test($ok, $label)
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . "\n";
    if (!$ok) $GLOBALS['failures']++;
}

function short_test_write($path, $content)
{
    $handle = fopen($path, 'w');
    fwrite($handle, $content);
    fclose($handle);
}

mkdir(KDO_ROOT, 0755);
mkdir(KDO_ROOT . '/cache', 0755);
$long = 'http://datcharrye.free.fr/listeKdo/';
$cache = KDO_ROOT . '/cache/short-' . sha1($long) . '.txt';
$retry = KDO_ROOT . '/cache/short-' . sha1($long) . '.retry';

short_test('https://tinyurl.com/abc-123' === short_link_normalize(" https://tinyurl.com/abc-123\n"), 'réponse TinyURL nettoyée');
short_test('https://tinyurl.com/abc123' === short_link_normalize('http://tinyurl.com/abc123'), 'ancienne réponse HTTP convertie en HTTPS');
foreach (array('', 'Error', '<html>Blocked</html>', 'https://tinyurl.com.evil.test/abc', 'https://tinyurl.com/abc?redirect=evil', "https://tinyurl.com/abc\ninjected") as $invalid) {
    short_test('' === short_link_normalize($invalid), 'réponse invalide refusée : ' . str_replace("\n", ' ', $invalid));
}
short_test('http://localhost:8090/listeKdo/' === short_link('http://localhost:8090/listeKdo/'), 'lien local inchangé');

short_test_write($cache, 'http://tinyurl.com/cached123');
short_test_write($retry, '');
short_test('https://tinyurl.com/cached123' === short_link($long), 'cache valide prioritaire sur un ancien échec');
short_test_write($cache, '');
short_test($long === short_link($long), 'échec récent : lien long disponible sans nouvel appel');
short_test_write($cache, 'https://evil.test/invalid');
short_test($long === short_link($long), 'cache invalide ignoré');

$code = 'f4ee1bdb482811f378523fe6eec17c65';
$listLong = $long . '?user=' . $code;
$oldCache = KDO_ROOT . '/cache/short-' . sha1($code) . '.txt';
$newCache = KDO_ROOT . '/cache/short-' . sha1($listLong) . '.txt';
$listRetry = KDO_ROOT . '/cache/short-' . sha1($listLong) . '.retry';
short_test_write($oldCache, 'https://tinyurl.com/old-list');
short_test_write($newCache, '');
short_test_write($listRetry, '');
short_test('https://tinyurl.com/old-list' === short_link($listLong), 'ancien cache de liste récupéré malgré un nouveau cache vide et un échec récent');
short_test_write($newCache, 'https://tinyurl.com/new-list');
short_test('https://tinyurl.com/new-list' === short_link($listLong), 'nouveau cache valide prioritaire');
short_test_write($newCache, '');
short_test_write($oldCache, 'https://evil.test/invalid');
short_test($listLong === short_link($listLong), 'ancien cache invalide refusé');
$invite = $listLong . '&invite=private-token';
short_test_write(KDO_ROOT . '/cache/short-' . sha1($invite) . '.retry', '');
short_test($invite === short_link($invite), 'invitation privée : aucun réemploi du lien public de la liste');

foreach (array('https://is.gd/Ab_123', 'http://v.gd/Ab123', 'https://da.gd/Ab123') as $short) {
    short_test('' !== short_link_normalize($short), 'service alternatif accepté : ' . $short);
}
short_test('' === short_link_normalize('https://da.gd/abc', 'tinyurl'), 'cache d’un autre service refusé en mode manuel');
$GLOBALS['short_test_provider'] = 'dagd';
short_test($listLong === short_link($listLong), 'choix da.gd : ancien cache TinyURL ignoré');
short_test(null !== short_link_browser($listLong), 'choix da.gd : recours au navigateur');
$GLOBALS['short_test_provider'] = 'none';
short_test($long === short_link($long), 'désactivation : lien complet malgré un cache existant');
short_test(null === short_link_browser($listLong), 'désactivation : aucun appel navigateur');
$GLOBALS['short_test_provider'] = 'auto';
short_test(null !== short_link_browser($listLong), 'mode automatique : recours au navigateur');
short_test_write(KDO_ROOT . '/cache/short-isgd-' . sha1($long) . '.txt', 'https://is.gd/old123');
short_test('https://is.gd/old123' === short_link($long), 'mode automatique : lien déjà créé avec un autre service conservé');
short_test(null === short_link_browser('http://localhost:8090/listeKdo/'), 'aucun lien local envoyé au service tiers');
$GLOBALS['short_test_provider'] = 'inconnu';
short_test('auto' === short_link_provider(), 'réglage inconnu : mode automatique');
unset($GLOBALS['short_test_provider']);
short_test('auto' === short_link_provider(), 'sans réglage : mode automatique');
$GLOBALS['short_test_provider'] = 'tinyurl';

if (isset($argv[1]) && '--live' === $argv[1]) {
    short_test_write($cache, '');
    unlink($retry);
    $short = short_link($long);
    short_test('' !== short_link_normalize($short), 'ancien cache vide : nouvelle tentative immédiate réussie');
    short_test($short === trim(implode('', file($cache))), 'lien court conservé dans le cache');
    short_test(!file_exists($retry), 'aucun marqueur d’échec après un succès');
    short_test_write($cache, '');
    short_test_write($retry, '');
    touch($retry, time() - 301);
    clearstatcache();
    short_test('' !== short_link_normalize(short_link($long)), 'échec de plus de cinq minutes : nouvelle tentative réussie');
    short_test(!file_exists($retry), 'ancien marqueur d’échec supprimé');
}

foreach (glob(KDO_ROOT . '/cache/*') as $path) unlink($path);
if (file_exists(KDO_ROOT . '/cache/.htaccess')) unlink(KDO_ROOT . '/cache/.htaccess');
rmdir(KDO_ROOT . '/cache');
rmdir(KDO_ROOT);
exit($failures ? 1 : 0);
?>
