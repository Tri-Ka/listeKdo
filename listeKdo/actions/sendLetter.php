<?php
/*
 * Envoie la lettre d'une liste en PDF (fabriqué par le navigateur : templates/print.php) à des adresses saisies.
 * Champs : owner (code de la liste), recipients, message (facultatif), pdf (fichier).
 * Un e-mail par destinataire (ils ne voient pas les autres adresses), depuis ListeKdo ; les réponses vont à
 * l'expéditeur si son adresse est confirmée. Limites : LETTER_MAX_RECIPIENTS par envoi,
 * LETTER_DAILY_RECIPIENTS par compte et par 24 h (lib/letter.php).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_once dirname(__FILE__) . '/../lib/letter.php';
require_post();
$me = require_login();

$owner = user_find_by_code(input('owner'));
$back = $owner ? '../index.php?user=' . rawurlencode($owner['code']) . '&page=imprimer' : list_url($me['code']);

if (!letter_mail_enabled()) {
    fail("L'envoi d'e-mails n'est pas disponible sur ce site.", $back, 409);
}
if (!$owner || !can_view($me, $owner)) {
    fail("Cette liste n'existe pas ou n'est pas visible.", $back, 403);
}

$recipients = letter_recipients(input('recipients'));
if (0 < count($recipients['invalid'])) {
    fail('Adresse e-mail non valide : ' . implode(', ', $recipients['invalid']) . '.', $back, 422);
}
$count = count($recipients['valid']);
if (0 === $count) {
    fail('Indiquez au moins une adresse e-mail.', $back, 422);
}
if ($count > LETTER_MAX_RECIPIENTS) {
    fail('Pas plus de ' . LETTER_MAX_RECIPIENTS . ' adresses à la fois.', $back, 422);
}
$left = letter_quota_left($me);
if ($count > $left) {
    fail(0 === $left
        ? 'Vous avez atteint la limite de ' . LETTER_DAILY_RECIPIENTS . ' envois sur 24 heures. Réessayez demain.'
        : 'Il vous reste ' . $left . ' envoi' . (1 < $left ? 's' : '') . ' possible' . (1 < $left ? 's' : '') . ' sur 24 heures.', $back, 429);
}

$message = trim(str_replace("\r", '', input('message')));
if (preg_match('/^.{' . (LETTER_MESSAGE_MAX + 1) . '}/su', $message)) {
    fail('Le message est trop long (' . LETTER_MESSAGE_MAX . ' caractères au plus).', $back, 422);
}

// Le PDF : un vrai fichier PDF, pas trop lourd.
$file = isset($_FILES['pdf']) ? $_FILES['pdf'] : null;
if (!$file || UPLOAD_ERR_OK !== $file['error'] || !is_uploaded_file($file['tmp_name'])) {
    fail("Le PDF n'a pas pu être envoyé. Réessayez.", $back, 422);
}
if ($file['size'] > LETTER_MAX_BYTES) {
    fail('Le PDF est trop lourd : décochez « Photos des idées » puis réessayez.', $back, 413);
}
$handle = fopen($file['tmp_name'], 'rb');
$pdf = $handle ? fread($handle, LETTER_MAX_BYTES + 1) : '';
if ($handle) {
    fclose($handle);
}
if ('%PDF-' !== substr($pdf, 0, 5) || strlen($pdf) > LETTER_MAX_BYTES) {
    fail("Ce fichier n'est pas un PDF valide.", $back, 422);
}

$theme = theme_of($owner);
$heading = theme_text($theme, 'heading', $owner);
$isOwn = (int) $me['id'] === (int) $owner['id'];
$link = is_private_list($owner) ? (can_share($me, $owner) ? list_invite_url($owner) : '') : share_url($owner);

$paragraphs = array(
    $me['nom'] . ' vous envoie ' . ($isOwn ? 'sa liste' : 'la liste') . ' « ' . $heading . ' » en PDF, en pièce jointe.'
        . ' De quoi trouver le cadeau qui fera plaisir !',
);
if ('' !== $message) {
    $paragraphs[] = 'Son petit mot : « ' . $message . ' »';
}
if ('' !== $link) {
    $paragraphs[] = 'La liste en ligne est toujours à jour : vous pouvez y voir les nouvelles idées et réserver un cadeau pour éviter les doublons.';
}

$replyTo = email_enabled() && email_confirmed($me) ? (string) $me['email'] : '';
$attachment = array('name' => 'liste-kdo-' . $owner['code'] . '.pdf', 'type' => 'application/pdf', 'data' => $pdf);

$sent = 0;
foreach ($recipients['valid'] as $to) {
    $ok = kdo_mail($to, $me['nom'] . ' vous envoie « ' . $heading . ' »', array(
        'preheader' => $heading . ' : la liste en PDF, en pièce jointe.',
        'title' => $heading . ' ' . $theme['emoji'],
        'hello' => '',
        'paragraphs' => $paragraphs,
        'button' => '' !== $link ? array('Voir la liste en ligne', $link) : null,
        'replyable' => '' !== $replyTo,
    ), array('reply_to' => $replyTo, 'attachments' => array($attachment)));
    if ($ok) {
        $sent++;
    }
}

if (0 < $sent) {
    letter_quota_add($me, $sent);
}
if (0 === $sent) {
    fail("L'e-mail n'a pas pu partir. Réessayez dans un instant.", $back, 500);
}

succeed(array('sent' => $sent), $back, $sent === $count
    ? 'Lettre envoyée à ' . $sent . ' destinataire' . (1 < $sent ? 's' : '') . ' !'
    : 'Lettre envoyée à ' . $sent . ' destinataire' . (1 < $sent ? 's' : '') . ' sur ' . $count . '. Vérifiez les autres adresses.');
