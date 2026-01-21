 <div class="row">
    <div class="col-xs-12">
        <div class="text-center">
            <label class="marged-top">
                Partager à vos amis
            </label>

            <div class="a2a_kit a2a_kit_size_32 share-btns marged-bottom">
                <a class="a2a_button_facebook"></a>
                <a class="a2a_button_twitter"></a>
                <a class="a2a_button_google_plus"></a>
                <?php if ($currentUser) : ?>
                    <input class="input-link-share" readonly="readonly" value="datcharrye.free.fr/listeKdo/?user=<?php echo $currentUser['code']; ?>">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
