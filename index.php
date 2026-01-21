<?php
    include 'config.php';
    include 'functions.php';

    $pageTitle = $currentTheme['title'] . (isset($currentUser) ? ' de ' . $currentUser['nom'] : '');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo $pageTitle; ?></title>
        <link rel="icon" href="./favicon.ico" type="image/x-icon">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta name="description" content="<?php echo $pageTitle; ?>">
        <meta property="og:title" content="<?php echo $pageTitle; ?>" />
        <meta property="og:image" content="http://datcharrye.free.fr/listeKdo/img/<?php echo $currentTheme['label']; ?>/metaOg.jpg" />
        <meta property="fb:app_id" content="185139272403079" />
        <meta name="google-signin-client_id" content="154877976324-hq9s2b5urubd0kimntpc6qqa0r55ru36.apps.googleusercontent.com">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link rel="stylesheet" type="text/css" media="screen" href="<?php echo auto_version('css/front/'.$currentTheme['label'].'/front.css'); ?>">
        <style>
        .loading {
            display: none; /* Initially hidden */
            font-size: 24px;
            margin-left: 10px;
        }
    </style>
    </head>

    <body class="">
        <?php if (isset($_SESSION['error']) && null !== $_SESSION['error']) : ?>
            <div class="error">
                <a href="#" class="error-close close" data-error-close><span aria-hidden="true">&times;</span></a>
                <p><i class="fa fa-warning"></i> <?php echo $_SESSION['error']; ?></p>
            </div>

            <?php $_SESSION['error'] = null; ?>
        <?php endif; ?>

        <section class="main-section">
            <?php include 'templates/main.php'; ?>
        </section>

        <?php include 'templates/modalObject.php'; ?>
        <?php include 'templates/modalUserNew.php'; ?>
        <?php include 'templates/modalConnect.php'; ?>
        <?php include 'templates/modalUserEdit.php'; ?>

        <script src="js/jquery-1.11.3.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/masonry.min.js"></script>
        <script src="js/autosize.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.2.0/imagesloaded.pkgd.min.js"></script>
        <div id="fb-root"></div>

        <script src="<?php echo auto_version('js/script.js'); ?>"></script>
        <script async src="https://static.addtoany.com/menu/page.js"></script>
        <script>(function(d, s, id) {
          var js, fjs = d.getElementsByTagName(s)[0];
          if (d.getElementById(id)) return;
          js = d.createElement(s); js.id = id;
          js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v2.8&appId=185139272403079";
          fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));</script>

        <script src="https://apis.google.com/js/platform.js" async defer></script>
    </body>
</html>
