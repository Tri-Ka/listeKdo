<?php
    include '../config.php';

    $userRepository = getUserRepository();

    $userRepository->updateUser($_SESSION['user']['id'], array('theme' => $_GET['theme']));
    $_SESSION['user']['theme'] = $_GET['theme'];

    header('Location: ../index.php');
    exit;
