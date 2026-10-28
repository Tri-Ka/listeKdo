<?php
/* Clé d'API GIPHY (recherche de GIF dans les commentaires), réservée aux administrateurs. */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
require_admin();

$back = '../admin.php?tab=settings';
$key = trim(input('key'));

if ('' !== $key && !preg_match('/^[A-Za-z0-9]{10,64}$/', $key)) fail('Cette clé GIPHY ne semble pas valide (lettres et chiffres uniquement).', $back);
if (!settings_enabled()) fail('Les réglages ne sont pas disponibles sur ce site.', $back);
if (false === setting_save('giphy_key', $key)) fail("Le réglage n'a pas pu être enregistré.", $back);
succeed(array(), $back, '' !== $key ? 'Clé GIPHY enregistrée : le bouton « GIF » est actif.' : 'Recherche de GIF désactivée.');
