<?php $me = $ctx['me']; ?>

<?php if (!$me) : ?>
    <dialog class="modal" id="login-dialog" aria-labelledby="login-title">
        <form method="post" action="actions/connect.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo e(input('user')); ?>">
            <header class="modal__header">
                <h2 id="login-title">Connexion</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <label class="field">
                    <span>Ton nom</span>
                    <input type="text" name="nom" required autocomplete="username">
                </label>
                <label class="field">
                    <span>Mot de passe</span>
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
            </div>
            <?php if (secret_enabled()) : ?>
                <p class="form__link"><button type="button" class="link-btn" data-open="forgot-dialog">Mot de passe oublié ?</button></p>
            <?php endif; ?>
            <footer class="modal__footer">
                <button type="button" class="btn btn--ghost" data-open="signup-dialog">Pas encore inscrit ?</button>
                <button type="submit" class="btn btn--primary">Se connecter</button>
            </footer>
        </form>
    </dialog>

    <dialog class="modal" id="signup-dialog" aria-labelledby="signup-title">
        <form method="post" action="actions/addUser.php" enctype="multipart/form-data" data-resize-images>
            <?php echo csrf_field(); ?>
            <header class="modal__header">
                <h2 id="signup-title">Créer ma liste</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <label class="field">
                    <span>Ton nom *</span>
                    <input type="text" name="nom" required maxlength="100" autocomplete="username">
                </label>
                <label class="field">
                    <span>Photo</span>
                    <input type="file" name="pictureFile" accept="image/*" data-max-size="600">
                </label>
                <?php echo render('partials/theme_field', array('current' => 'noel')); ?>
                <label class="field">
                    <span>Mot de passe *</span>
                    <input type="password" name="password" required minlength="4" autocomplete="new-password">
                </label>
                <label class="field">
                    <span>Répéter le mot de passe *</span>
                    <input type="password" name="re-password" required minlength="4" autocomplete="new-password" data-match="password">
                </label>
                <?php if (secret_enabled()) : ?>
                    <?php echo render('partials/secret_fields', array('current' => '', 'required' => false)); ?>
                <?php endif; ?>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--ghost" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary">Créer</button>
            </footer>
        </form>
    </dialog>
    <?php if (secret_enabled()) : ?>
        <dialog class="modal modal--small" id="forgot-dialog" aria-labelledby="forgot-title">
            <form method="post" action="actions/resetPassword.php" data-forgot>
                <?php echo csrf_field(); ?>
                <header class="modal__header">
                    <h2 id="forgot-title">Mot de passe oublié</h2>
                    <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
                </header>
                <div class="modal__body form">
                    <label class="field">
                        <span>Ton nom</span>
                        <input type="text" name="nom" required autocomplete="username">
                    </label>
                    <div class="forgot__step" data-forgot-step hidden>
                        <p class="forgot__question" data-forgot-question></p>
                        <label class="field">
                            <span>Ta réponse</span>
                            <input type="text" name="secret_answer" autocomplete="off">
                        </label>
                        <label class="field">
                            <span>Nouveau mot de passe</span>
                            <input type="password" name="password" minlength="4" autocomplete="new-password">
                        </label>
                        <label class="field">
                            <span>Répéter le mot de passe</span>
                            <input type="password" name="re-password" minlength="4" autocomplete="new-password">
                        </label>
                    </div>
                </div>
                <footer class="modal__footer">
                    <button type="button" class="btn btn--ghost" data-open="login-dialog">Retour</button>
                    <button type="submit" class="btn btn--primary" data-forgot-submit>Continuer</button>
                </footer>
            </form>
        </dialog>
    <?php endif; ?>
<?php else : ?>
    <dialog class="modal" id="profile-dialog" aria-labelledby="profile-title">
        <form method="post" action="actions/editUser.php" enctype="multipart/form-data" data-resize-images>
            <?php echo csrf_field(); ?>
            <header class="modal__header">
                <h2 id="profile-title">Mon profil</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <label class="field">
                    <span>Ton nom *</span>
                    <input type="text" name="nom" required maxlength="100" value="<?php echo e($me['nom']); ?>" autocomplete="username">
                </label>
                <label class="field">
                    <span>Nouvelle photo</span>
                    <input type="file" name="pictureFile" accept="image/*" data-max-size="600">
                </label>
                <?php echo render('partials/theme_field', array('current' => $me['theme'])); ?>
                <?php if (secret_enabled()) : ?>
                    <?php echo render('partials/secret_fields', array('current' => $me['secret_question'], 'required' => false)); ?>
                <?php endif; ?>
                <?php if (event_dates_enabled()) : ?>
                    <label class="field">
                        <span>Date de l'événement <small>(ma date de naissance pour un anniversaire, la date du mariage ou de la naissance prévue ; Noël tombe toujours le 25/12)</small></span>
                        <input type="date" name="event_date" value="<?php echo e($me['event_date'] && '0000-00-00' !== $me['event_date'] ? $me['event_date'] : ''); ?>">
                    </label>
                <?php endif; ?>
                <?php if (array_key_exists('message', $me)) : ?>
                    <label class="field">
                        <span>Mon petit mot <small>(affiché en bas de ma liste)</small></span>
                        <textarea name="message" rows="3" maxlength="500" data-autosize placeholder="Ex. : Merci à tous, j'ai hâte de vous voir !"><?php echo e($me['message']); ?></textarea>
                    </label>
                <?php endif; ?>
                <label class="field">
                    <span>Nouveau mot de passe</span>
                    <input type="password" name="password" minlength="4" autocomplete="new-password" placeholder="Laisser vide pour ne pas changer">
                </label>
                <label class="field">
                    <span>Répéter le mot de passe</span>
                    <input type="password" name="re-password" autocomplete="new-password" data-match="password">
                </label>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--ghost" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </footer>
        </form>
    </dialog>
<?php endif; ?>

<?php if ($ctx['canEdit']) : ?>
    <dialog class="modal" id="object-form-dialog" aria-labelledby="object-form-title">
        <form method="post" action="actions/addObject.php" enctype="multipart/form-data" data-object-form data-resize-images
            data-add-action="actions/addObject.php" data-edit-action="actions/editObject.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="object_id" value="">
            <input type="hidden" name="owner" value="<?php echo e($ctx['owner']['code']); ?>">
            <header class="modal__header">
                <h2 id="object-form-title" data-object-form-title>Une nouvelle idée ?</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <label class="field">
                    <span>Lien vers le Kdo</span>
                    <input type="url" name="link" inputmode="url" placeholder="https://… (le nom et l'image seront remplis automatiquement)" data-metadata-source>
                    <small class="field__status" data-metadata-status aria-live="polite"></small>
                </label>
                <label class="field">
                    <span>Nom du Kdo *</span>
                    <input type="text" name="nom" required maxlength="255">
                </label>
                <?php if (prices_enabled()) : ?>
                    <label class="field field--price">
                        <span>Prix indicatif</span>
                        <input type="text" name="price" inputmode="decimal" placeholder="ex. 29,90" autocomplete="off">
                        <span class="field__suffix">€</span>
                    </label>
                <?php endif; ?>
                <label class="field">
                    <span>Description</span>
                    <textarea name="description" rows="4" data-autosize></textarea>
                </label>
                <?php if (items_enabled()) : ?>
                    <div class="collection" data-collection>
                        <label class="collection__toggle">
                            <input type="checkbox" name="collection" value="1" role="switch" data-collection-toggle>
                            <span>
                                <strong>Ce cadeau est une collection</strong>
                                <small>Plusieurs éléments à offrir séparément (les tomes d'une BD, des figurines…)</small>
                            </span>
                        </label>
                        <div class="collection__editor" data-collection-editor hidden>
                            <ol class="collection__list" data-collection-list></ol>
                            <button type="button" class="btn btn--soft btn--sm" data-collection-add><?php echo icon('plus'); ?> Ajouter un élément</button>
                        </div>
                        <template data-collection-row>
                            <li class="collection__row">
                                <input type="hidden" name="item_ids[]" value="">
                                <input type="text" name="items[]" maxlength="255" placeholder="Nom de l'élément (ex. Tome 3)" aria-label="Nom de l'élément">
                                <button type="button" class="round-btn round-btn--sm" data-collection-remove aria-label="Retirer cet élément"><?php echo icon('xmark'); ?></button>
                            </li>
                        </template>
                    </div>
                <?php endif; ?>
                <div class="field-group">
                    <label class="field">
                        <span>Lien vers l'image</span>
                        <input type="text" name="image" inputmode="url" placeholder="https://…">
                    </label>
                    <span class="field-group__or">ou</span>
                    <label class="field">
                        <span>Image depuis l'appareil</span>
                        <input type="file" name="file" accept="image/*" data-max-size="1600">
                    </label>
                </div>
                <img class="image-preview" alt="" data-image-preview hidden>
                <p class="form__tip"><?php echo icon('puzzle-piece'); ?> Plus rapide : <button type="button" class="link-btn" data-open="extension-dialog">l'extension Chrome</button> ajoute un produit depuis sa page.</p>
            </div>
            <footer class="modal__footer">
                <button type="submit" class="btn btn--danger" form="delete-object-form" data-delete-object hidden
                    data-confirm="Êtes-vous sûr de vouloir supprimer cette belle idée ?" aria-label="Supprimer"><?php echo icon('trash-can'); ?></button>
                <span class="modal__spacer"></span>
                <button type="button" class="btn btn--ghost" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary" data-object-form-submit>Ajouter</button>
            </footer>
        </form>
        <form method="post" action="actions/deleteObject.php" id="delete-object-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="">
        </form>
    </dialog>
<?php endif; ?>

<dialog class="modal" id="extension-dialog" aria-labelledby="extension-title">
    <header class="modal__header">
        <h2 id="extension-title">Extension Chrome</h2>
        <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
    </header>
    <div class="modal__body">
        <p class="extension__intro">
            <span class="extension__logo"><?php echo icon('chrome'); ?></span>
            Sur la page d'un produit, n'importe quelle boutique : un clic sur l'icône cadeau et il est ajouté à votre liste,
            avec son nom, sa photo, son prix et son lien.
        </p>

        <a class="btn btn--primary btn--lg extension__download" href="<?php echo e(asset('download/liste-kdo-extension.zip')); ?>" download="liste-kdo-extension.zip">
            <?php echo icon('download'); ?> Télécharger l'extension
        </a>

        <ol class="extension__steps">
            <li>Décompressez le fichier téléchargé.</li>
            <li>Dans Chrome, ouvrez <code>chrome://extensions</code> et activez le <strong>Mode développeur</strong> (en haut à droite).</li>
            <li>Cliquez sur <strong>Charger l'extension non empaquetée</strong> et choisissez le dossier <code>liste-kdo-extension</code>.</li>
            <li>Épinglez l'icône cadeau avec <?php echo icon('puzzle-piece'); ?> dans la barre de Chrome.</li>
            <li>Connectez-vous à ce site dans Chrome : l'extension utilise votre compte.</li>
        </ol>

        <p class="extension__note">
            Fonctionne avec Chrome, Edge, Brave et Opera, sur ordinateur.
            Quand une nouvelle version sort, une flèche apparaît sur l'icône cadeau : ouvrez l'extension pour la télécharger.
        </p>
    </div>
</dialog>

<?php if ($me && children_enabled()) : ?>
    <dialog class="modal" id="child-new-dialog" aria-labelledby="child-new-title">
        <form method="post" action="actions/addChild.php" enctype="multipart/form-data" data-resize-images>
            <?php echo csrf_field(); ?>
            <header class="modal__header">
                <h2 id="child-new-title">Créer la liste d'un enfant</h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <p class="form__intro">Vous gérez sa liste depuis votre compte : ajouter des idées, changer le thème… L'enfant n'a pas besoin de compte. Vous continuez à voir ce qui lui est offert, pour coordonner.</p>
                <label class="field">
                    <span>Prénom *</span>
                    <input type="text" name="nom" required maxlength="100">
                </label>
                <label class="field">
                    <span>Photo</span>
                    <input type="file" name="pictureFile" accept="image/*" data-max-size="600">
                </label>
                <?php echo render('partials/theme_field', array('current' => 'birthday')); ?>
                <?php if (event_dates_enabled()) : ?>
                    <label class="field">
                        <span>Date de naissance <small>(ou date prévue pour une liste de naissance)</small></span>
                        <input type="date" name="event_date">
                    </label>
                <?php endif; ?>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--ghost" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary">Créer la liste</button>
            </footer>
        </form>
    </dialog>
<?php endif; ?>

<?php if ($ctx['canEdit'] && !$ctx['isOwner']) : ?>
    <?php $child = $ctx['owner']; ?>
    <dialog class="modal" id="child-dialog" aria-labelledby="child-title">
        <form method="post" action="actions/editChild.php" enctype="multipart/form-data" data-resize-images>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="owner" value="<?php echo e($child['code']); ?>">
            <header class="modal__header">
                <h2 id="child-title">Liste de <?php echo e($child['nom']); ?></h2>
                <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <label class="field">
                    <span>Prénom *</span>
                    <input type="text" name="nom" required maxlength="100" value="<?php echo e($child['nom']); ?>">
                </label>
                <label class="field">
                    <span>Nouvelle photo</span>
                    <input type="file" name="pictureFile" accept="image/*" data-max-size="600">
                </label>
                <?php echo render('partials/theme_field', array('current' => $child['theme'])); ?>
                <?php if (event_dates_enabled()) : ?>
                    <label class="field">
                        <span>Date de naissance <small>(ou date prévue pour une liste de naissance)</small></span>
                        <input type="date" name="event_date" value="<?php echo e($child['event_date'] && '0000-00-00' !== $child['event_date'] ? $child['event_date'] : ''); ?>">
                    </label>
                <?php endif; ?>
                <?php
                $managers = child_managers($child['id']);
                $managerIds = array();
                foreach ($managers as $manager) {
                    $managerIds[] = (int) $manager['id'];
                }
                $candidates = array();
                foreach (user_friends($me['id']) as $friend) {
                    if (!in_array((int) $friend['id'], $managerIds) && (int) $friend['id'] !== (int) $child['id']) {
                        $candidates[] = $friend;
                    }
                }
                ?>
                <div class="field managers">
                    <span>Parents qui gèrent cette liste</span>
                    <ul class="managers__list">
                        <?php foreach ($managers as $manager) : ?>
                            <li class="child-chip">
                                <?php echo avatar($manager, 'child-chip__avatar'); ?>
                                <?php echo (int) $manager['id'] === (int) $me['id'] ? 'Vous' : e($manager['nom']); ?>
                                <?php if (1 < count($managers)) : ?>
                                    <button type="submit" formaction="actions/childManager.php" formnovalidate name="manager_remove" value="<?php echo (int) $manager['id']; ?>"
                                        class="managers__remove" aria-label="Retirer <?php echo e($manager['nom']); ?>"
                                        data-confirm="<?php echo e($manager['nom']); ?> ne pourra plus gérer cette liste. Continuer ?"><?php echo icon('xmark'); ?></button>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (0 < count($candidates)) : ?>
                        <div class="managers__add">
                            <select name="manager_id" aria-label="Ajouter un parent">
                                <option value="">Ajouter un parent parmi mes amis…</option>
                                <?php foreach ($candidates as $candidate) : ?>
                                    <option value="<?php echo (int) $candidate['id']; ?>"><?php echo e($candidate['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" formaction="actions/childManager.php" formnovalidate name="manager_add" value="1" class="btn btn--soft btn--sm"><?php echo icon('plus'); ?> Ajouter</button>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (array_key_exists('message', $child)) : ?>
                    <label class="field">
                        <span>Petit mot <small>(affiché en bas de sa liste)</small></span>
                        <textarea name="message" rows="3" maxlength="500" data-autosize><?php echo e($child['message']); ?></textarea>
                    </label>
                <?php endif; ?>
            </div>
            <footer class="modal__footer">
                <button type="submit" class="btn btn--danger" form="delete-child-form" data-confirm="Supprimer la liste de <?php echo e($child['nom']); ?> et toutes ses idées ?" aria-label="Supprimer la liste"><?php echo icon('trash-can'); ?></button>
                <span class="modal__spacer"></span>
                <button type="button" class="btn btn--ghost" data-close>Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </footer>
        </form>
        <form method="post" action="actions/deleteChild.php" id="delete-child-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="owner" value="<?php echo e($child['code']); ?>">
        </form>
    </dialog>
<?php endif; ?>

<?php if ($ctx['canGift'] && participations_enabled()) : ?>
    <dialog class="modal modal--small" id="gift-choice-dialog" aria-labelledby="gift-choice-title">
        <header class="modal__header">
            <h2 id="gift-choice-title">Comment l'offrir ?</h2>
            <button type="button" class="modal__close" data-close aria-label="Fermer"><?php echo icon('xmark'); ?></button>
        </header>
        <div class="modal__body">
            <p class="gift-choice__name" data-gift-choice-name></p>
            <form method="post" action="actions/objectGifted.php" data-ajax="gift">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="" data-gift-choice-id>
                <button type="submit" class="gift-choice" data-gift-alone>
                    <span class="gift-choice__icon"><?php echo icon('gift'); ?></span>
                    <span><strong>Je l'offre seul</strong><small>Le cadeau est réservé à votre nom.</small></span>
                    <?php echo icon('chevron-right', 'gift-choice__chevron'); ?>
                </button>
            </form>
            <button type="button" class="gift-choice" data-gift-group>
                <span class="gift-choice__icon gift-choice__icon--group"><?php echo icon('users'); ?></span>
                <span><strong>On se cotise à plusieurs</strong><small>Vous lancez une cagnotte : d'autres peuvent se joindre à vous.</small></span>
                <?php echo icon('chevron-right', 'gift-choice__chevron'); ?>
            </button>
        </div>
    </dialog>
<?php endif; ?>

<?php if ($me && secret_enabled() && '' === (string) $me['secret_question'] && !empty($_SESSION['kdo_ask_secret'])) : ?>
    <?php unset($_SESSION['kdo_ask_secret']); ?>
    <dialog class="modal modal--small" id="secret-invite-dialog" aria-labelledby="secret-invite-title" data-autoopen>
        <form method="post" action="actions/setSecret.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="back" value="<?php echo e($ctx['owner'] ? $ctx['owner']['code'] : $me['code']); ?>">
            <header class="modal__header">
                <h2 id="secret-invite-title">Protégez votre compte 🔐</h2>
                <button type="button" class="modal__close" data-close aria-label="Plus tard"><?php echo icon('xmark'); ?></button>
            </header>
            <div class="modal__body form">
                <p class="form__intro">Choisissez une question secrète : si vous oubliez votre mot de passe, sa réponse vous permettra d'en choisir un nouveau.</p>
                <?php echo render('partials/secret_fields', array('current' => '', 'required' => true)); ?>
            </div>
            <footer class="modal__footer">
                <button type="button" class="btn btn--ghost" data-close>Plus tard</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </footer>
        </form>
    </dialog>
<?php endif; ?>
