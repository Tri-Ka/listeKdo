<?php
    session_start();
    session_destroy();

    unset($_COOKIE['listeKdoUserCode']);
    setcookie("listeKdoUserCode", '', 1 ,'/');
    setcookie("listKdoGoogleAuth", '', 0 ,'/listeKdo');

    header('Location: ../index.php');
