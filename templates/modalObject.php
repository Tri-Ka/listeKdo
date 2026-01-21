<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <form action="actions/addObject.php" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times"></i></span></button>
                    <h3 class="modal-title" id="myModalLabel">Une nouvelle idée ?</h3>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12 col-md-10 col-md-offset-1">
                            <input type="hidden" name="user_id" value="<?php echo $currentUser['id']; ?>">

                            <div class="form-group">
                                <label for="nom">Nom du Kdo *</label>
                                <input type="text" class="form-control" name="nom" placeholder="nom" required="required" data-object-name>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" placeholder="Description" rows="6" data-object-desc></textarea>
                            </div>

                            <div class="form-group link-img-input">
                                <label for="image">Lien vers l'image</label>
                                <input type="text" class="form-control" name="image" placeholder="Lien vers l'image (optionnel)" data-object-imgurl>
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
                                <input type="text" class="form-control" name="link" placeholder="Lien vers l'objet (optionnel)" data-object-url>
                            </div>

                            <p>* champs obligatoires</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                    <input type="submit" class="btn btn-primary" value="Ajouter">
                </div>
            </div>
        </form>
    </div>
</div>
