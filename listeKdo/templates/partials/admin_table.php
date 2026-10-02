<?php
$params = $table['params'];
$rows = $table['rows'];
$isLists = 'lists' === $params['tab'];
$themes = themes();
$roles = roles();
$from = 0 < $table['total'] ? ($params['page'] - 1) * $params['per'] + 1 : 0;
$to = min($table['total'], $params['page'] * $params['per']);
?>
<div class="dt__bar">
    <nav class="dt__filters" aria-label="Filtres">
        <?php foreach (admin_filters($params['tab']) as $key => $label) : ?>
            <a class="dt__chip" href="<?php echo e(admin_url($params, array('filter' => $key, 'page' => 1))); ?>" data-dt-link
                <?php echo $params['filter'] === $key ? 'aria-current="true"' : ''; ?>><?php echo e($label); ?></a>
        <?php endforeach; ?>
    </nav>
    <p class="dt__count" aria-live="polite">
        <?php if (0 < $table['total']) : ?>
            <strong><?php echo (int) $from; ?>–<?php echo (int) $to; ?></strong> sur <?php echo (int) $table['total']; ?>
        <?php else : ?>
            Aucun résultat
        <?php endif; ?>
    </p>
</div>

<div class="dt__scroll">
    <table class="dt">
        <thead>
            <tr>
                <?php if ($isLists) : ?>
                    <?php echo admin_th($params, 'nom', 'Liste'); ?>
                    <?php echo admin_th($params, 'theme', 'Thème'); ?>
                    <?php if (event_dates_enabled()) : ?><?php echo admin_th($params, 'event', 'Événement'); ?><?php endif; ?>
                    <?php echo admin_th($params, 'ideas', 'Idées', 'dt__num'); ?>
                    <?php echo admin_th($params, 'gifted', 'Offertes', 'dt__num'); ?>
                    <?php echo admin_th($params, 'followers', 'Abonnés', 'dt__num'); ?>
                    <?php echo admin_th($params, 'last_idea', 'Dernière idée'); ?>
                    <th scope="col" class="dt__actions"><span class="sr-only">Actions</span></th>
                <?php else : ?>
                    <?php echo admin_th($params, 'nom', 'Utilisateur'); ?>
                    <?php echo admin_th($params, 'role', 'Rôle'); ?>
                    <?php echo admin_th($params, 'ideas', 'Idées', 'dt__num'); ?>
                    <?php echo admin_th($params, 'friends', 'Amis', 'dt__num'); ?>
                    <?php if (children_enabled()) : ?><?php echo admin_th($params, 'children', 'Gère', 'dt__num'); ?><?php endif; ?>
                    <?php if (last_seen_enabled()) : ?><?php echo admin_th($params, 'last_seen', 'Dernière visite'); ?><?php endif; ?>
                    <?php if (secret_enabled()) : ?><th scope="col" class="dt__num">Question</th><?php endif; ?>
                    <th scope="col" class="dt__actions"><span class="sr-only">Actions</span></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <?php
                $isMe = (int) $row['id'] === (int) $me['id'];
                $isChild = !empty($row['is_child']) || admin_is_child_account($row);
                $listUrl = 'index.php?user=' . rawurlencode($row['code']);
                ?>
                <tr>
                    <td class="dt__who" data-label="<?php echo $isLists ? 'Liste' : 'Utilisateur'; ?>">
                        <a href="<?php echo e($listUrl); ?>" class="dt__person">
                            <?php echo avatar($row, 'dt__avatar', false); ?>
                            <span>
                                <strong><?php echo e($row['nom']); ?></strong>
                                <small>
                                    #<?php echo (int) $row['id']; ?>
                                    <?php if ($isChild) : ?> · <span class="dt__tag dt__tag--child"><?php echo icon('layer-group'); ?> Secondaire</span><?php endif; ?>
                                    <?php if ($isMe) : ?> · <span class="dt__tag">Vous</span><?php endif; ?>
                                </small>
                            </span>
                        </a>
                    </td>

                    <?php if ($isLists) : ?>
                        <?php $theme = isset($themes[$row['theme']]) ? $themes[$row['theme']] : null; ?>
                        <td data-label="Thème">
                            <span class="dt__theme dt__theme--<?php echo e($row['theme']); ?>"><?php echo e($theme ? $theme['label'] : $row['theme']); ?></span>
                        </td>
                        <?php if (event_dates_enabled()) : ?>
                            <td data-label="Événement"><?php echo '' !== admin_date($row['event_date']) ? e(admin_date($row['event_date'])) : '<span class="dt__muted">—</span>'; ?></td>
                        <?php endif; ?>
                        <td class="dt__num" data-label="Idées">
                            <?php echo (int) $row['ideas']; ?>
                            <?php if (0 < (int) $row['received']) : ?><small class="dt__muted" title="Reçues"> (<?php echo (int) $row['received']; ?> reçue<?php echo 1 < (int) $row['received'] ? 's' : ''; ?>)</small><?php endif; ?>
                        </td>
                        <td class="dt__num" data-label="Offertes">
                            <?php $percent = 0 < (int) $row['ideas'] ? round(100 * (int) $row['gifted'] / (int) $row['ideas']) : 0; ?>
                            <span class="dt__progress" title="<?php echo (int) $percent; ?> %">
                                <span><?php echo (int) $row['gifted']; ?></span>
                                <span class="dt__bar-track"><span style="width: <?php echo (int) $percent; ?>%"></span></span>
                            </span>
                        </td>
                        <td class="dt__num" data-label="Abonnés"><?php echo (int) $row['followers']; ?></td>
                        <td data-label="Dernière idée">
                            <?php echo '' !== admin_date($row['last_idea']) ? e(admin_date($row['last_idea'])) : '<span class="dt__muted">—</span>'; ?>
                            <?php if ('' !== (string) $row['managers']) : ?>
                                <small class="dt__sub">Gérée par <?php echo e($row['managers']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="dt__actions">
                            <div class="dt__btns">
                            <a class="round-btn round-btn--sm" href="<?php echo e($listUrl); ?>" data-tip="Voir la liste" aria-label="Voir la liste de <?php echo e($row['nom']); ?>"><?php echo icon('eye'); ?></a>
                                <?php if (children_enabled()) : ?>
                                    <button type="button" class="round-btn round-btn--sm" data-admin-managers="<?php echo (int) $row['id']; ?>" data-name="<?php echo e($row['nom']); ?>" data-tip="Gestionnaires (liste secondaire)" aria-label="Gestionnaires de <?php echo e($row['nom']); ?>"><?php echo icon('layer-group'); ?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php else : ?>
                        <td data-label="Rôle">
                            <?php if ($isMe || $isChild || !roles_enabled()) : ?>
                                <span class="dt__role dt__role--<?php echo e($row['role']); ?>">
                                    <?php echo 'admin' === $row['role'] ? icon('crown') : ''; ?>
                                    <?php echo e(isset($roles[$row['role']]) ? $roles[$row['role']] : $row['role']); ?>
                                </span>
                            <?php else : ?>
                                <form method="post" action="actions/adminRole.php" class="dt__role-form" data-admin-role>
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <select name="role" class="dt__select dt__select--<?php echo e($row['role']); ?>" aria-label="Rôle de <?php echo e($row['nom']); ?>">
                                        <?php foreach ($roles as $key => $label) : ?>
                                            <option value="<?php echo e($key); ?>"<?php echo $key === $row['role'] ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <noscript><button type="submit" class="btn btn--sm">OK</button></noscript>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="dt__num" data-label="Idées"><?php echo (int) $row['ideas']; ?></td>
                        <td class="dt__num" data-label="Amis"><?php echo (int) $row['friends']; ?></td>
                        <?php if (children_enabled()) : ?>
                            <td class="dt__num" data-label="Listes gérées"><?php echo 0 < (int) $row['children'] ? (int) $row['children'] : '<span class="dt__muted">—</span>'; ?></td>
                        <?php endif; ?>
                        <?php if (last_seen_enabled()) : ?>
                            <td data-label="Dernière visite">
                                <?php if ('' !== (string) $row['last_seen_at']) : ?>
                                    <?php $recent = strtotime($row['last_seen_at']) > time() - 7 * 86400; ?>
                                    <span class="dt__seen<?php echo $recent ? ' is-recent' : ''; ?>" title="<?php echo e(date('d/m/Y à H:i', strtotime($row['last_seen_at']))); ?>">
                                        <?php echo e(admin_seen($row['last_seen_at'])); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="dt__muted">Jamais</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if (secret_enabled()) : ?>
                            <td class="dt__num" data-label="Question secrète">
                                <?php if ($isChild) : ?>
                                    <span class="dt__muted">—</span>
                                <?php elseif (!empty($row['has_secret'])) : ?>
                                    <span class="dt__ok" title="Question secrète choisie"><?php echo icon('circle-check'); ?><span class="sr-only">Oui</span></span>
                                <?php else : ?>
                                    <span class="dt__muted" title="Pas de question secrète">Non</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="dt__actions">
                            <div class="dt__btns">
                            <a class="round-btn round-btn--sm" href="<?php echo e($listUrl); ?>" data-tip="Voir la liste" aria-label="Voir la liste de <?php echo e($row['nom']); ?>"><?php echo icon('eye'); ?></a>
                                <?php if (children_enabled()) : ?>
                                    <button type="button" class="round-btn round-btn--sm" data-admin-managers="<?php echo (int) $row['id']; ?>" data-name="<?php echo e($row['nom']); ?>" data-tip="Gestionnaires (liste secondaire)" aria-label="Gestionnaires de <?php echo e($row['nom']); ?>"><?php echo icon('layer-group'); ?></button>
                                <?php endif; ?>
                            <?php if (!admin_is_child_account($row) && reset_links_enabled()) : ?>
                                <form method="post" action="actions/adminResetLink.php" data-admin-link>
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button type="submit" class="round-btn round-btn--sm" data-tip="Lien de secours" aria-label="Lien de secours pour <?php echo e($row['nom']); ?>"><?php echo icon('link'); ?></button>
                                </form>
                            <?php endif; ?>
                            <?php if (!admin_is_child_account($row)) : ?>
                                <form method="post" action="actions/adminPassword.php" data-admin-confirm="password" data-name="<?php echo e($row['nom']); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button type="submit" class="round-btn round-btn--sm" data-tip="Nouveau mot de passe" aria-label="Nouveau mot de passe pour <?php echo e($row['nom']); ?>"><?php echo icon('key'); ?></button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$isMe && 'admin' !== $row['role']) : ?>
                                <form method="post" action="actions/adminDeleteUser.php" data-admin-confirm="delete" data-name="<?php echo e($row['nom']); ?>" data-ideas="<?php echo (int) $row['ideas']; ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button type="submit" class="round-btn round-btn--sm dt__danger" data-tip="Supprimer" aria-label="Supprimer <?php echo e($row['nom']); ?>"><?php echo icon('trash-can'); ?></button>
                                </form>
                            <?php endif; ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (0 === count($rows)) : ?>
                <tr class="dt__empty"><td colspan="9">Aucun résultat<?php echo '' !== $params['q'] ? ' pour « ' . e($params['q']) . ' »' : ''; ?>.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer class="dt__footer">
    <label class="dt__per">
        Lignes par page
        <select data-dt-per>
            <?php foreach (array(10, 25, 50, 100) as $per) : ?>
                <option value="<?php echo e(admin_url($params, array('per' => $per, 'page' => 1))); ?>"<?php echo $per === $params['per'] ? ' selected' : ''; ?>><?php echo (int) $per; ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <?php if (1 < $params['pages']) : ?>
        <nav class="dt__pages" aria-label="Pagination">
            <?php if (1 < $params['page']) : ?>
                <a class="dt__page" href="<?php echo e(admin_url($params, array('page' => $params['page'] - 1))); ?>" data-dt-link aria-label="Page précédente"><?php echo icon('chevron-left'); ?></a>
            <?php else : ?>
                <span class="dt__page is-disabled" aria-hidden="true"><?php echo icon('chevron-left'); ?></span>
            <?php endif; ?>

            <?php foreach (admin_page_numbers($params['page'], $params['pages']) as $number) : ?>
                <?php if (0 === $number) : ?>
                    <span class="dt__page dt__page--gap" aria-hidden="true">…</span>
                <?php elseif ($number === $params['page']) : ?>
                    <span class="dt__page is-current" aria-current="page"><?php echo (int) $number; ?></span>
                <?php else : ?>
                    <a class="dt__page" href="<?php echo e(admin_url($params, array('page' => $number))); ?>" data-dt-link><?php echo (int) $number; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($params['page'] < $params['pages']) : ?>
                <a class="dt__page" href="<?php echo e(admin_url($params, array('page' => $params['page'] + 1))); ?>" data-dt-link aria-label="Page suivante"><?php echo icon('chevron-right'); ?></a>
            <?php else : ?>
                <span class="dt__page is-disabled" aria-hidden="true"><?php echo icon('chevron-right'); ?></span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</footer>
