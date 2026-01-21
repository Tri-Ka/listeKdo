<div class="modal fade" id="connectModal" tabindex="-1" role="dialog" aria-labelledby="connectModal">
    <div class="modal-dialog" role="document">
        <form action="actions/connect.php" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times"></i></span></button>
                    <h3 class="modal-title" id="myModalLabel">Connexion</h3>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12 col-md-8 col-md-offset-2">
                            <div class="form-group">
                                <label for="nom">Ton nom *</label>
                                <input type="text" class="form-control" name="nom" placeholder="nom" required="required">
                            </div>

                            <div class="form-group">
                                <label for="description">Mot de passe *</label>
                                <input type="password" class="form-control" name="password" placeholder="Mot de passe" required="required">
                            </div>

                            <p>* champs obligatoires</p>

                            <div class="row" style="margin: 15px 0;">
                                <div class="col-xs-12 col-sm-6 col-sm-offset-3">
                                    <input type="submit" class="btn btn-primary btn-lg btn-block" value="Se connecter">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer text-right">
                    <button type="button" class="btn btn-default full-xs" data-dismiss="modal">Annuler</button>

                    <a class="btn btn-primary full-xs no-log" href="#" data-toggle="modal" data-target="#addUserModal"  data-target="connectModal" data-dismiss="modal">
                        Pas encore inscrit ?
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
