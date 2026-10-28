<?php
/*
 * Ajouter ou corriger son adresse e-mail depuis les fenêtres obligatoires (adresse manquante,
 * ou « Mauvaise adresse ? » dans la fenêtre du code). Un code de confirmation y est envoyé.
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();
$back = input('back') ? list_url(input('back')) : list_url($me['code']);

if (!email_enabled()) {
    fail("L'adresse e-mail n'est pas encore activée sur ce site.", $back, 500);
}

$message = email_save($me, email_from_request($back, $me['id']));

succeed(array(), $back, '' !== $message ? $message : 'Adresse e-mail enregistrée : vous pourrez aussi vous connecter avec.');
