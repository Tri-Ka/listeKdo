<?php
/* Choix et test du service de liens courts, réservés aux administrateurs. */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
require_admin();

$back = '../admin.php?tab=settings';
$provider = input('provider');
$options = short_link_options();
if (!isset($options[$provider])) fail('Choisissez un service proposé dans la liste.', $back);

if ('test' === input('op')) {
    $long = KDO_SITE_URL;
    $browser = short_link_browser($long, $provider);
    $result = array('url' => '', 'message' => 'Le raccourcissement est désactivé.');
    if (null === $browser && 'none' !== $provider) $result = short_link_request($provider, $long);
    succeed(array(
        'url' => '' !== $result['url'] ? $result['url'] : $long,
        'long' => $long,
        'browser' => $browser,
        'message' => $result['message'],
    ), $back);
}

if ('save' !== input('op')) fail('Action inconnue.', $back);
if (!settings_enabled()) fail('Les réglages ne sont pas disponibles sur ce site.', $back);
if (false === setting_save('short_link_provider', $provider)) fail("Le réglage n'a pas pu être enregistré.", $back);
succeed(array(), $back, 'Service de liens courts enregistré.');
