<?php
    // Configuration pour Docker
    // Décommentez cette section et commentez la configuration Free.fr pour utiliser Docker
    
    if (!isset($_SESSION)) {
        session_start();
    }

    // Configuration pour environnement Docker
    $_host = 'db';  // Nom du service MySQL dans docker-compose.yml
    $_db = 'listekdo';
    $_username = 'listekdo_user';
    $_pass = 'listekdo_pass';

    // Configuration originale Free.fr (commentée)
    // $_host = 'sql.free.fr';
    // $_db = 'datcharrye';
    // $_username = 'datcharrye';
    // $_pass = 'spx728';

    $link = mysqli_connect($_host, $_username, $_pass, $_db);

    if (!$link) {
        die('Erreur de connexion MySQL : ' . mysqli_connect_error());
    }

    if (!mysqli_set_charset($link, 'utf8mb4')) {
        die('Erreur de configuration du jeu de caractères : ' . mysqli_error($link));
    }
?>
