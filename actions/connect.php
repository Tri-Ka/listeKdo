<?php
    include '../config.php';

    $nom = $_POST['nom'];
    $password = $_POST['password'];

    $userRepository = getUserRepository();
    $user = $userRepository->findByName($nom);

    if ($user && $user->password === md5($password)) {
        $_SESSION['user'] = $user->toArray();
        setcookie('listeKdoUserCode', $user->code, time()+31556926 ,'/' );

        header('Location: ../index.php?user='.$user->code);
        exit;
    }

    $_SESSION['error'] = 'nom ou mot de passe invalide';
    header('Location: ../index.php');
    exit;
