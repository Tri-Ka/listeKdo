<?php
    include '../config.php';

    $userRepository = getUserRepository();

    $theme = isset($_GET['theme']) ? trim((string) $_GET['theme']) : '';
    if ('' === $theme || !isset($_SESSION['user']['id'])) {
        header('Location: ../index.php');
        exit;
    }

    $userRepository->updateUser($_SESSION['user']['id'], array('theme' => $theme));
    $_SESSION['user']['theme'] = $theme;

    header('Location: ../index.php');
    exit;
