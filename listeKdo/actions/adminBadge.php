<?php
/*
 * Administration des badges : enregistrer / ajouter un badge (op=save), installer les badges par défaut manquants (op=install).
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
require_admin();

$back = '../admin.php?tab=badges';
if (!badges_enabled()) {
    fail("Les tables des badges n'existent pas (sql/2026-10-03-badges.sql).", $back);
}

if ('install' === input('op')) {
    $added = badges_install();
    succeed(array('added' => $added), $back, 0 < $added ? $added . ' badge(s) par défaut ajouté(s).' : 'Tous les badges par défaut sont déjà là.');
}

// L'admin voit tout de suite l'effet d'une modification (sinon, mise à jour au plus toutes les 30 s).
unset($_SESSION['kdo_badges_at']);

$metrics = badge_metrics();
$tiers = badge_tiers();
$kinds = badge_kinds();

$values = array(
    'name' => substr(input('name'), 0, 80),
    'description' => substr(input('description'), 0, 255),
    'emoji' => substr(input('emoji'), 0, 32),
    'metric' => input('metric'),
    'threshold' => max(1, input_int('threshold')),
    'kind' => input('kind'),
    'tier' => input('tier'),
    'position' => input_int('position'),
    'active' => '1' === input('active') ? 1 : 0,
    'secret' => '1' === input('secret') ? 1 : 0,
);

if ('' === $values['name'] || '' === $values['emoji']) {
    fail('Le nom et l\'emoji sont obligatoires.', $back);
}
if (!isset($metrics[$values['metric']]) || !isset($tiers[$values['tier']]) || !isset($kinds[$values['kind']])) {
    fail('Indicateur, niveau ou type inconnu.', $back);
}

$id = input_int('id');
if (0 < $id) {
    if (!db_one('SELECT id FROM badge WHERE id = ?', array($id))) {
        fail('Badge introuvable.', $back, 404);
    }
    db_update('badge', $values, array('id' => $id));
    // Seuil baissé : ceux qui l'atteignent l'obtiendront à leur prochaine visite. Remonté : ceux qui l'ont le gardent.
    succeed(array(), $back, '« ' . $values['name'] . ' » enregistré.');
}

$values['code'] = 'custom-' . substr(sha1(uniqid(mt_rand(), true)), 0, 10);
db_insert('badge', $values);
succeed(array(), $back, '« ' . $values['name'] . ' » ajouté.');
