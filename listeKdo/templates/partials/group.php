<?php
/*
 * Cadeau à plusieurs (fiche détaillée) : participants et formulaire pour participer.
 */
$id = (int) $object['id'];
$me = $ctx['me'];
$mine = null;
foreach ($object['participants'] as $participant) {
    if ((int) $participant['user_id'] === (int) $me['id']) {
        $mine = $participant;
    }
}
$percent = null !== $object['price'] && 0 < $object['price'] ? min(100, round($object['group_total'] / $object['price'] * 100)) : null;
?>
<section class="group" data-group-slot="<?php echo $id; ?>">
    <h3><?php echo icon('users'); ?> Offrir à plusieurs</h3>

    <?php if (0 < count($object['participants'])) : ?>
        <?php if (null !== $percent) : ?>
            <div class="group__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $percent; ?>">
                <span style="width: <?php echo (int) $percent; ?>%"></span>
            </div>
            <p class="group__total"><strong><?php echo e(format_price($object['group_total'])); ?></strong> sur <?php echo e(format_price($object['price'])); ?></p>
        <?php endif; ?>
        <ul class="group__list">
            <?php foreach ($object['participants'] as $participant) : ?>
                <li>
                    <?php echo avatar($participant['user'], 'group__avatar'); ?>
                    <span><?php echo (int) $participant['user_id'] === (int) $me['id'] ? 'Vous' : e($participant['user']['nom']); ?></span>
                    <span class="group__amount"><?php echo null !== $participant['amount'] && 0 < (float) $participant['amount'] ? e(format_price((float) $participant['amount'])) : '—'; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="group__hint">Un gros cadeau ? Proposez de l'offrir à plusieurs : les autres pourront se joindre à vous.</p>
    <?php endif; ?>

    <?php if (null === $object['gifted_by']) : ?>
        <form class="group__form" method="post" action="actions/participate.php" data-ajax="participation">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <label class="group__amount-field">
                <span>Ma participation (facultatif)</span>
                <input type="text" name="amount" inputmode="decimal" placeholder="ex. 30" value="<?php echo $mine && null !== $mine['amount'] ? e(str_replace('.', ',', (string) (float) $mine['amount'])) : ''; ?>">
                <span class="group__currency">€</span>
            </label>
            <button type="submit" name="do" value="join" class="btn btn--primary"><?php echo icon('users'); ?> <?php echo $mine ? 'Modifier' : 'Je participe'; ?></button>
            <?php if ($mine) : ?>
                <button type="submit" name="do" value="leave" class="btn btn--ghost" data-confirm="Votre participation à ce cadeau commun sera retirée." data-confirm-title="Quitter ce cadeau commun ?" data-confirm-ok="Me retirer" data-confirm-icon="users">Me retirer</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</section>
