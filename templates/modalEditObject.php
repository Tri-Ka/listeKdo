<div class="modal fade" id="modal-edit-object-<?php echo $object->id; ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <form action="actions/editObject.php" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times"></i></span></button>
                    <h3 class="modal-title" id="myModalLabel">Modifier: <?php echo $object->nom; ?></h3>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12 col-md-10 col-md-offset-1">
                            <input type="hidden" name="user_id" value="<?php echo $currentUser['id']; ?>">
                            <input type="hidden" name="object_id" value="<?php echo $object->id; ?>">

                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" class="form-control" name="nom" placeholder="nom" required="required" value="<?php echo $object->nom; ?>">
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" placeholder="Description" rows="6"><?php echo $object->description; ?></textarea>
                            </div>

                            <div class="form-group link-img-input">
                                <label for="image">Lien vers l'image</label>
                                <input type="text" class="form-control" name="image" placeholder="Lien vers l'image (optionnel)" value="<?php echo $object->image_url; ?>">
                            </div>

                            <div class="text-center or-div">
                                ou
                            </div>

                            <div class="form-group link-file-input">
                                <label for="image">Image de l'objet</label>
                                <input type="file" class="form-control" name="file" placeholder="Fichier image">
                            </div>

                            <div class="form-group">
                                <label for="link">Lien vers l'objet</label>
                                <input type="text" class="form-control" name="link" placeholder="Lien vers l'objet (optionnel)" value="<?php echo $object->link; ?>">
                            </div>

                            <p>* champs obligatoires</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <a class="btn btn-danger" href="actions/deleteObject.php?id=<?php echo $object->id; ?>" data-confirm="Êtes-vous sûr de vouloir supprimer cette belle idée ?">
                        <i class="fa fa-trash"></i>
                    </a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                    <input type="submit" class="btn btn-primary" value="Modifier">
                </div>
            </div>
        </form>
    </div>
</div>
