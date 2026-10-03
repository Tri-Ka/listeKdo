<?php
/* Vue d'ensemble : Apache ECharts, avec une version CSS si le CDN est indisponible. */
$maxIdeas = 1;
foreach ($insights['activity'] as $month) {
    $maxIdeas = max($maxIdeas, (int) $month['ideas']);
}
$totalIdeas = max(1, (int) $insights['ideas']['total']);
$giftedPercent = min(100, round(100 * (int) $insights['ideas']['gifted'] / $totalIdeas));
$receivedPercent = min(100 - $giftedPercent, round(100 * (int) $insights['ideas']['received'] / $totalIdeas));
$maxHolders = 1;
foreach ($insights['badges']['popular'] as $badge) {
    $maxHolders = max($maxHolders, (int) $badge['holders']);
}
$activity = array('labels' => array(), 'values' => array());
foreach ($insights['activity'] as $month) {
    $activity['labels'][] = $month['label'];
    $activity['values'][] = (int) $month['ideas'];
}
$ideaData = array(
    'labels' => received_enabled() ? array('Disponibles', 'Réservées', 'Reçues') : array('Disponibles', 'Réservées'),
    'values' => array((int) ($insights['ideas']['total'] - $insights['ideas']['gifted'] - $insights['ideas']['received']), (int) $insights['ideas']['gifted']),
);
if (received_enabled()) {
    $ideaData['values'][] = (int) $insights['ideas']['received'];
}
$badgeData = array('labels' => array(), 'values' => array());
foreach ($insights['badges']['popular'] as $badge) {
    $badgeData['labels'][] = $badge['emoji'] . ' ' . $badge['name'];
    $badgeData['values'][] = (int) $badge['holders'];
}
$badgeTrend = array('labels' => array(), 'values' => array());
$gemTrend = array('labels' => array(), 'values' => array());
foreach ($insights['activity'] as $month) {
    $badgeTrend['labels'][] = $month['label'];
    $badgeTrend['values'][] = (int) $month['badges'];
    $gemTrend['labels'][] = $month['label'];
    $gemTrend['values'][] = (int) $month['spent'];
}
$visitTrend = array('labels' => array(), 'values' => array());
foreach ($insights['visits']['days'] as $day) {
    $visitTrend['labels'][] = $day['label'];
    $visitTrend['values'][] = (int) $day['value'];
}
$skinPurchases = $insights['gems']['skins'];
$skinData = array('labels' => array(), 'values' => array());
$maxSkinPurchases = 1;
foreach ($skinPurchases as $skin) {
    $skinData['labels'][] = $skin['label'];
    $skinData['values'][] = (int) $skin['purchases'];
    $maxSkinPurchases = max($maxSkinPurchases, (int) $skin['purchases']);
}
$skinChartWidth = max(360, count($skinPurchases) * 74);
$referralPercent = $insights['referrals']['total'] ? round(100 * $insights['referrals']['active'] / $insights['referrals']['total']) : 0;
?>
<section class="insights" aria-labelledby="insights-title">
    <div class="insights__head">
        <div>
            <p class="insights__eyebrow">En un coup d’œil</p>
            <h2 id="insights-title">Vie de la communauté</h2>
        </div>
        <p>Les chiffres se mettent à jour avec les données du site.</p>
    </div>

    <div class="insights__grid">
        <article class="insight-card insight-card--activity">
            <div class="insight-card__head"><div><h3>Activité récente</h3><p>Idées ajoutées, sur les 6 derniers mois.</p></div><span class="insight-card__legend"><i></i> Idées</span></div>
            <div class="chart-wrap chart-wrap--activity" data-chart="activity" data-chart-values="<?php echo e(kdo_json($activity)); ?>">
                <div class="chart-canvas" hidden aria-label="Nombre d'idées ajoutées au cours des six derniers mois"></div>
                <div class="activity-chart chart-wrap__fallback" role="img" aria-label="Nombre d'idées ajoutées au cours des six derniers mois">
                    <?php foreach ($insights['activity'] as $month) : ?>
                        <?php $height = max(5, round(100 * (int) $month['ideas'] / $maxIdeas)); ?>
                        <div class="activity-chart__column">
                            <span class="activity-chart__value"><?php echo (int) $month['ideas']; ?></span>
                            <span class="activity-chart__bar" style="height: <?php echo (int) $height; ?>%" title="<?php echo e($month['label']); ?> : <?php echo (int) $month['ideas']; ?> idée(s)"></span>
                            <span class="activity-chart__label"><?php echo e($month['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <article class="insight-card insight-card--ideas">
            <div class="insight-card__head"><div><h3>État des idées</h3><p>La répartition de toutes les idées.</p></div></div>
            <div class="chart-wrap chart-wrap--donut" data-chart="ideas" data-chart-values="<?php echo e(kdo_json($ideaData)); ?>">
                <div class="chart-canvas" hidden aria-label="Répartition des idées disponibles, réservées et reçues"></div>
            <div class="idea-status chart-wrap__fallback">
                <div class="idea-status__donut" style="--gifted: <?php echo (int) $giftedPercent; ?>; --received: <?php echo (int) $receivedPercent; ?>" aria-label="<?php echo (int) $giftedPercent; ?> % réservées et <?php echo (int) $receivedPercent; ?> % reçues"><strong><?php echo (int) $insights['ideas']['total']; ?></strong><span>idées</span></div>
                <ul class="idea-status__legend">
                    <li><i class="is-available"></i><span>Disponibles</span><strong><?php echo (int) ($insights['ideas']['total'] - $insights['ideas']['gifted'] - $insights['ideas']['received']); ?></strong></li>
                    <li><i class="is-gifted"></i><span>Réservées</span><strong><?php echo (int) $insights['ideas']['gifted']; ?></strong></li>
                    <?php if (received_enabled()) : ?><li><i class="is-received"></i><span>Reçues</span><strong><?php echo (int) $insights['ideas']['received']; ?></strong></li><?php endif; ?>
                </ul>
            </div>
            </div>
        </article>

        <article class="insight-card insight-card--counters">
            <div class="insight-card__head"><div><h3>Les compteurs</h3><p>Les interactions qui font vivre le site.</p></div></div>
            <ul class="counter-grid">
                <?php foreach ($insights['counters'] as $counter) : ?><li class="counter-grid__<?php echo e($counter['key']); ?>"><strong><?php echo (int) $counter['value']; ?></strong><span><?php echo e($counter['label']); ?></span></li><?php endforeach; ?>
            </ul>
        </article>

        <article class="insight-card insight-card--presence">
            <div class="insight-card__head"><div><h3>Présence</h3><p>Personnes ayant ouvert le site récemment.</p></div></div>
            <?php if ($insights['presence']['enabled']) : ?><ul class="presence-grid">
                <li><strong><?php echo (int) $insights['presence']['now']; ?></strong><span>maintenant</span></li>
                <li><strong><?php echo (int) $insights['presence']['day']; ?></strong><span>24 h</span></li>
                <li><strong><?php echo (int) $insights['presence']['week']; ?></strong><span>7 jours</span></li>
                <li><strong><?php echo (int) $insights['presence']['month']; ?></strong><span>30 jours</span></li>
            </ul><?php else : ?><p class="insight-card__empty">Installez <code>2026-10-02-derniere-visite.sql</code> pour suivre l’activité récente.</p><?php endif; ?>
        </article>

        <article class="insight-card insight-card--visits">
            <div class="insight-card__head"><div><h3>Visiteurs uniques</h3><p>Nombre de comptes connectés, jour par jour.</p></div></div>
            <?php if ($insights['visits']['enabled']) : ?><div class="chart-wrap chart-wrap--visits" data-chart="activity" data-chart-values="<?php echo e(kdo_json($visitTrend)); ?>">
                <div class="chart-canvas" hidden aria-label="Visiteurs uniques sur les quatorze derniers jours"></div>
                <p class="insight-card__empty chart-wrap__fallback">Le graphique est disponible lorsque JavaScript est activé.</p>
            </div><?php else : ?><p class="insight-card__empty">Installez <code>2026-10-04-statistiques-visites.sql</code> : l’historique démarre ensuite, sans données personnelles.</p><?php endif; ?>
        </article>

        <?php if ($insights['badges']['enabled']) : ?>
        <article class="insight-card insight-card--badges">
            <div class="insight-card__head"><div><h3>Badges & trophées</h3><p><?php echo (int) $insights['badges']['earned']; ?> obtenus par <?php echo (int) $insights['badges']['holders']; ?> personne(s).</p></div><span class="badge-summary"><?php echo (int) $insights['badges']['defined']; ?> en jeu</span></div>
            <?php if (count($insights['badges']['popular'])) : ?><div class="chart-wrap chart-wrap--badges" data-chart="badges" data-chart-values="<?php echo e(kdo_json($badgeData)); ?>">
                <div class="chart-canvas" hidden aria-label="Badges les plus obtenus"></div>
                <ol class="badge-chart chart-wrap__fallback">
                    <?php foreach ($insights['badges']['popular'] as $badge) : ?><li><span class="badge-chart__name"><?php echo e($badge['emoji']); ?> <?php echo e($badge['name']); ?></span><span class="badge-chart__track"><i style="width: <?php echo (int) round(100 * $badge['holders'] / $maxHolders); ?>%"></i></span><strong><?php echo (int) $badge['holders']; ?></strong></li><?php endforeach; ?>
                </ol>
            </div><?php else : ?><p class="insight-card__empty">Les premiers badges apparaîtront ici dès qu’ils seront obtenus.</p><?php endif; ?>
        </article>
        <article class="insight-card insight-card--badges-trend">
            <div class="insight-card__head"><div><h3>Badges gagnés</h3><p>Attributions au cours des 6 derniers mois.</p></div></div>
            <div class="chart-wrap chart-wrap--trend" data-chart="trend" data-chart-values="<?php echo e(kdo_json($badgeTrend)); ?>">
                <div class="chart-canvas" hidden aria-label="Badges obtenus au cours des six derniers mois"></div>
                <p class="insight-card__empty chart-wrap__fallback">Le graphique est disponible lorsque JavaScript est activé.</p>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($insights['referrals']['enabled']) : ?>
        <article class="insight-card insight-card--referrals">
            <div class="insight-card__head"><div><h3>Parrainage</h3><p>Des filleuls inscrits à leur première idée.</p></div></div>
            <div class="referral-stat"><strong><?php echo (int) $insights['referrals']['active']; ?></strong><span>filleul(s) actif(s) sur <?php echo (int) $insights['referrals']['total']; ?></span></div>
            <div class="referral-progress" aria-label="<?php echo (int) $referralPercent; ?> % des filleuls sont actifs"><i style="width: <?php echo (int) $referralPercent; ?>%"></i></div>
            <p class="referral-rate"><?php echo (int) $referralPercent; ?> % de conversion</p>
        </article>
        <?php endif; ?>

        <?php if ($insights['gems']['enabled']) : ?>
        <article class="insight-card insight-card--gems">
            <div class="insight-card__head"><div><h3>Économie des gemmes</h3><p><?php echo (int) $insights['gems']['purchases']; ?> habillage(s) achetés · <?php echo (int) $insights['gems']['spent']; ?> gemmes dépensées.</p></div></div>
            <div class="chart-wrap chart-wrap--trend" data-chart="trend" data-chart-values="<?php echo e(kdo_json($gemTrend)); ?>">
                <div class="chart-canvas" hidden aria-label="Gemmes dépensées au cours des six derniers mois"></div>
                <p class="insight-card__empty chart-wrap__fallback">Le graphique est disponible lorsque JavaScript est activé.</p>
            </div>
            <?php if (count($insights['gems']['skins'])) : ?>
            <details class="skin-purchases">
                <summary>Voir les habillages achetés</summary>
                <ul>
                    <?php foreach ($insights['gems']['skins'] as $skin) : ?>
                    <li><span><?php echo e($skin['label']); ?></span><strong><?php echo (int) $skin['purchases']; ?> achat<?php echo 1 === (int) $skin['purchases'] ? '' : 's'; ?> · <?php echo (int) $skin['spent']; ?> gemmes</strong></li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
        </article>
        <article class="insight-card insight-card--skins">
            <div class="insight-card__head"><div><h3>Achats d’habillages</h3><p>Tous les articles de boutique achetés, par popularité.</p></div></div>
            <?php if (count($skinPurchases)) : ?><div class="chart-wrap chart-wrap--skins" style="--chart-width: <?php echo (int) $skinChartWidth; ?>px" data-chart="skins" data-chart-values="<?php echo e(kdo_json($skinData)); ?>">
                <div class="chart-canvas" hidden aria-label="Tous les habillages achetés"></div>
                <ol class="badge-chart chart-wrap__fallback">
                    <?php foreach ($skinPurchases as $skin) : ?><li><span class="badge-chart__name"><?php echo e($skin['label']); ?></span><span class="badge-chart__track"><i style="width: <?php echo (int) round(100 * $skin['purchases'] / $maxSkinPurchases); ?>%"></i></span><strong><?php echo (int) $skin['purchases']; ?></strong></li><?php endforeach; ?>
                </ol>
            </div><?php else : ?><p class="insight-card__empty">Les premiers achats d’habillages apparaîtront ici.</p><?php endif; ?>
        </article>
        <?php endif; ?>
    </div>
</section>
