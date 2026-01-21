<?php
    include '../config.php';

    $friendCode = $_GET['friendCode'];
    $userRepository = getUserRepository();

    $friend = $userRepository->findByCode($friendCode);

    if ($friend) {
        $userRepository->removeFriendRelation($_SESSION['user']['id'], $friendCode);
        $userRepository->removeFriendRelation($friend->id, $_SESSION['user']['code']);

        header('Location: ../index.php?user='.$friendCode);
        exit;
    }

    $_SESSION['error'] = 'ami introuvable';
    header('Location: ../index.php');
    exit;
