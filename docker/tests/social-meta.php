<?php
/* Vérification ciblée PHP 4 : confidentialité des aperçus, échappement HTML et fichiers générés. */
define('KDO_ROOT', dirname(__FILE__) . '/../../listeKdo');
require_once KDO_ROOT . '/lib/security.php';
require_once KDO_ROOT . '/lib/models.php';
require_once KDO_ROOT . '/lib/view.php';
require_once KDO_ROOT . '/lib/social.php';
$failures = 0;
$socialInput = array();

function input($key)
{
    return isset($GLOBALS['socialInput'][$key]) ? $GLOBALS['socialInput'][$key] : '';
}

function db_has_column($table, $column)
{
    return true;
}

function db_has_table($table)
{
    return false;
}

function social_check($ok, $label)
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . "\n";
    if (!$ok) $GLOBALS['failures']++;
}

$themes = themes();
$theme = $themes['birthday'];
$theme['key'] = 'birthday';
$owner = array('id' => 999, 'nom' => 'Nom confidentiel', 'code' => 'demo', 'is_private' => 1,
    'list_title' => 'Titre confidentiel', 'invite_token' => 'jeton-confidentiel');
$visitor = social_metadata(array('owner' => $owner, 'canView' => false), $theme, false);
$manager = social_metadata(array('owner' => $owner, 'canView' => true), $theme, false);
social_check($visitor === $manager, 'liste privée : aperçu identique pour visiteur et gestionnaire');
$privateText = implode(' ', $manager);
social_check(false === strpos($privateText, $owner['nom']) && false === strpos($privateText, $owner['list_title'])
    && false === strpos($privateText, $owner['invite_token']), 'liste privée : aucun nom, titre personnel ni jeton dans les métadonnées');
social_check($manager['noIndex'] && false !== strpos($manager['image'], '/private.jpg'), 'liste privée : image neutre et exclusion des moteurs');
$owner['is_private'] = 0;
$owner['list_title'] = 'Envies & "amis" <script>alert(1)</script>';
$public = social_metadata(array('owner' => $owner), $theme, false);
social_check(false !== strpos($public['title'], $owner['list_title']), 'liste publique : le titre personnalisé est conservé');
$rendered = render('partials/social_meta', array('meta' => $public));
social_check(false === strpos($rendered, '<script>') && false !== strpos($rendered, '&lt;script&gt;')
    && false !== strpos($rendered, '&quot;'), 'titre personnalisé : caractères HTML et guillemets échappés');
$guide = social_metadata(array('owner' => $owner), $theme, true);
social_check(false === strpos($guide['title'], $owner['list_title']) && false !== strpos($guide['url'], 'page=comment-ca-marche'), 'guide : aperçu indépendant de la liste connectée');

foreach (array('home', 'birthday', 'noel', 'naissance', 'mariage', 'wishlist', 'private') as $key) {
    $size = getimagesize(KDO_ROOT . '/img/social/' . $key . '.jpg');
    social_check($size && 1200 === $size[0] && 630 === $size[1] && 2 === $size[2], 'visuel ' . $key . ' : JPEG 1200 × 630');
}
exit($failures ? 1 : 0);
