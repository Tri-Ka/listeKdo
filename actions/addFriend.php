<?php
    include '../config.php';

    $friendCode = isset($_GET['friendCode']) ? trim((string) $_GET['friendCode']) : '';
    if ('' === $friendCode) {
        $_SESSION['error'] = 'ami introuvable';
        header('Location: ../index.php');
        exit;
    }
    $userRepository = getUserRepository();

    $friend = $userRepository->findByCode($friendCode);

    if ($friend) {
        $userRepository->addFriendRelation($_SESSION['user']['id'], $friendCode);
        $userRepository->addFriendRelation($friend->id, $_SESSION['user']['code']);

        header('Location: ../index.php?user='.$friendCode);
        exit;
    }

    $_SESSION['error'] = 'ami introuvable';
    header('Location: ../index.php');
    exit;
