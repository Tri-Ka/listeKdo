<?php
    include '../config.php';

    $nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if ('' === $nom || '' === $password) {
        $_SESSION['error'] = 'nom ou mot de passe invalide';
        header('Location: ../index.php');
        exit;
    }

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
