<?php
/* Droits du partage privé et première invitation affichée dans deux fenêtres, sous PHP 4. */
require_once dirname(__FILE__) . '/../../listeKdo/lib/models.php';
$failures = 0;
$inviteToken = '';
$inviteWrites = 0;
$invitesAvailable = true;

function db_has_column($table, $column)
{
    return 'invite_token' !== $column || $GLOBALS['invitesAvailable'];
}

function db_has_table($table)
{
    return true;
}

function db_one($query, $params)
{
    if (false !== strpos($query, 'SELECT invite_token')) {
        return array('invite_token' => $GLOBALS['inviteToken']);
    }
    if (false !== strpos($query, 'liste_manager') && 2 === $params[1]) {
        return array('child_id' => $params[0]);
    }
    return null;
}

function db_update($table, $values, $where)
{
    $GLOBALS['inviteToken'] = $values['invite_token'];
    $GLOBALS['inviteWrites']++;
}

function random_token()
{
    return sha1('invitation-' . $GLOBALS['inviteWrites']);
}

function share_url($owner)
{
    return 'http://localhost/listeKdo/?user=' . $owner['code'];
}

function private_share_check($ok, $label)
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . "\n";
    if (!$ok) $GLOBALS['failures']++;
}

$owner = array('id' => 1, 'code' => 'owner', 'is_private' => 1, 'invite_token' => '');
private_share_check(can_share($owner, $owner), 'liste privée : propriétaire autorisé');
private_share_check(can_share(array('id' => 2), $owner), 'liste privée : gestionnaire autorisé');
private_share_check(!can_share(array('id' => 3), $owner), 'liste privée : invité sans droit de partage');
private_share_check(!can_share(null, $owner), 'liste privée : visiteur anonyme sans droit de partage');
private_share_check(!can_share($owner, null), 'liste inaccessible : pas de partage');

$first = list_invite_url($owner);
$second = list_invite_url($owner);
private_share_check($first === $second && 1 === $inviteWrites, 'premier affichage : les deux fenêtres partagent un seul jeton');
private_share_check($first === share_url($owner) . '&invite=' . $inviteToken, 'le lien affiché contient le jeton enregistré');
list_invite_reset($owner);
private_share_check($first !== list_invite_url($owner), 'renouvellement : les fenêtres utilisent le nouveau jeton');

$invitesAvailable = false;
private_share_check(!can_share($owner, $owner), 'migration absente : pas de partage privé sans invitation');
$owner['is_private'] = 0;
private_share_check(can_share(null, $owner), 'liste publique : partage accessible à tous');
exit($failures ? 1 : 0);
