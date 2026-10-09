<?php
/* Badges : tableau filtrable, ajout et édition en modale. */
$metrics = badge_metrics();
$tiers = badge_tiers();
$kinds = badge_kinds();
$tierKeys = array_keys($tiers);
$hasGems = skins_enabled();
$gemDefaults = array();
if ($hasGems) {
    foreach ($kinds as $kind => $label) {
        $gemDefaults[$kind] = array();
        foreach ($tiers as $tier => $label) $gemDefaults[$kind][$tier] = badge_default_gems($kind, $tier);
    }
}
$blank = array('id' => 0, 'code' => '', 'kind' => 'badge', 'name' => '', 'description' => '', 'emoji' => '🏅', 'tier' => 'bronze',
    'metric' => 'ideas', 'threshold' => 1, 'gems' => 0, 'secret' => 0, 'active' => 1, 'position' => (count($badges) + 1) * 10, 'holders' => 0);
$columns = array('name' => 'Badge', 'category' => 'Type', 'level' => 'Niveau', 'threshold' => 'Condition');
if ($hasGems) $columns['amount'] = 'Gemmes';
$columns['holders'] = 'Obtenu par';
$columns['state'] = 'État';
?>
<div class="dt__bar">
    <p class="dt__count"><strong><?php echo count($badges); ?></strong> badges et trophées.</p>
    <form method="post" action="actions/adminBadge.php">
        <?php echo csrf_field(); ?>
        <button type="submit" name="op" value="install" class="btn btn--light btn--sm" title="Ajoute les badges par défaut qui manquent">Badges par défaut manquants</button>
    </form>
</div>
<div class="badge-admin" data-badge-admin>
    <button type="button" class="btn btn--primary badge-admin__add" data-badge-create><?php echo icon('plus'); ?> Ajouter un badge</button>
    <section class="panel badge-table" data-badge-table data-badge-defaults="<?php echo e(kdo_json($gemDefaults)); ?>" aria-label="Gestion des badges">
        <?php echo render('partials/admin_table_head', array('prefix' => 'badge', 'caption' => 'Rechercher dans les badges', 'placeholder' => 'Rechercher un badge…')); ?>
        <div class="dt__bar" data-badge-controls hidden>
            <div class="dt__filters">
            <select class="dt__select" data-badge-filter="category" aria-label="Filtrer par type">
                <option value="">Tous les types</option>
                <?php foreach ($kinds as $kind) : ?><option value="<?php echo e($kind); ?>"><?php echo e($kind); ?></option><?php endforeach; ?>
            </select>
            <select class="dt__select" data-badge-filter="tier" aria-label="Filtrer par niveau">
                <option value="">Tous les niveaux</option>
                <?php foreach ($tiers as $key => $label) : ?><option value="<?php echo e($key); ?>"><?php echo e($label); ?></option><?php endforeach; ?>
            </select>
            <select class="dt__select" data-badge-filter="state" aria-label="Filtrer par état">
                <option value="">Tous les états</option><option value="Actif">Actifs</option><option value="Désactivé">Désactivés</option>
            </select>
            </div>
            <p class="dt__count" data-badge-count aria-live="polite"></p>
        </div>
        <div class="dt__scroll">
            <table class="dt badge-table__grid">
                <thead><tr>
                    <?php foreach ($columns as $key => $label) : ?>
                        <?php echo render('partials/admin_local_th', array('prefix' => 'badge', 'column' => $key, 'label' => $label, 'numeric' => in_array($key, array('amount', 'holders')))); ?>
                    <?php endforeach; ?>
                    <th scope="col" class="dt__actions"><span class="sr-only">Actions</span></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($badges as $badge) : ?>
                        <?php $state = $badge['active'] ? 'Actif' : 'Désactivé'; $amount = $hasGems ? badge_gems($badge) : 0; ?>
                        <tr data-badge-row data-badge-definition="<?php echo e(kdo_json($badge)); ?>" data-id="<?php echo (int) $badge['id']; ?>" data-name="<?php echo e($badge['name']); ?>" data-category="<?php echo e($kinds[$badge['kind']]); ?>" data-tier="<?php echo e($badge['tier']); ?>" data-level="<?php echo (int) array_search($badge['tier'], $tierKeys); ?>" data-threshold="<?php echo (int) $badge['threshold']; ?>" data-amount="<?php echo (int) $amount; ?>" data-holders="<?php echo (int) $badge['holders']; ?>" data-state="<?php echo e($state); ?>">
                            <td data-label="Badge" class="badge-table__name"><div class="badge-table__person">
                                <span class="badge-admin__medal medal--<?php echo e($badge['tier']); ?>" data-badge-medal><?php echo e($badge['emoji']); ?></span>
                                <span><strong data-badge-name><?php echo e($badge['name']); ?></strong><small class="dt__sub" data-badge-description><?php echo e($badge['description']); ?></small></span>
                            </div></td>
                            <td data-label="Type" data-badge-kind><?php echo e($kinds[$badge['kind']]); ?></td>
                            <td data-label="Niveau"><span class="badge-admin__chip medal--<?php echo e($badge['tier']); ?>" data-badge-tier><?php echo e($tiers[$badge['tier']]); ?></span></td>
                            <td data-label="Condition"><span data-badge-metric><?php echo e($metrics[$badge['metric']]); ?></span><small class="dt__sub">Seuil : <span data-badge-threshold><?php echo (int) $badge['threshold']; ?></span></small></td>
                            <?php if ($hasGems) : ?>
                                <td data-label="Gemmes" class="dt__num"><strong data-badge-amount><?php echo (int) $amount; ?></strong><small class="dt__sub" data-badge-auto><?php echo 0 === (int) $badge['gems'] ? 'Automatique' : 'Personnalisé'; ?></small></td>
                            <?php endif; ?>
                            <td data-label="Obtenu par" class="dt__num"><?php echo (int) $badge['holders']; ?></td>
                            <td data-label="État"><span class="badge-admin__chip<?php echo !$badge['active'] ? ' badge-admin__chip--off' : ''; ?>" data-badge-state><?php echo e($state); ?></span><small class="dt__sub" data-badge-secret><?php echo $badge['secret'] ? 'Secret' : ''; ?></small></td>
                            <td data-label="Actions" class="dt__actions"><button type="button" class="btn btn--light btn--sm" data-badge-edit aria-haspopup="dialog" aria-controls="badge-edit-dialog">Modifier</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-badge-empty hidden><td colspan="<?php echo count($columns) + 1; ?>" class="dt__empty">Aucun badge pour cette recherche.</td></tr>
                </tbody>
            </table>
        </div>
        <?php echo render('partials/admin_local_footer', array('prefix' => 'badge', 'paginationLabel' => 'Pagination des badges')); ?>
    </section>
    <dialog class="modal badge-edit-dialog" id="badge-edit-dialog" aria-labelledby="badge-edit-title" data-badge-blank="<?php echo e(kdo_json($blank)); ?>">
        <header class="modal__header">
            <h2 id="badge-edit-title">Modifier un badge</h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <?php echo render('partials/admin_badge_form', array('badge' => $blank)); ?>
    </dialog>
</div>
