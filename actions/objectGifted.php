<?php
    include '../config.php';

    if (!isset($_SESSION['user']['id'])) {
        header('Location: ../index.php');
        exit;
    }

    $objectId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $friendId = isset($_GET['friendId']) ? trim((string) $_GET['friendId']) : '';
    if (0 === $objectId) {
        header('Location: ../index.php');
        exit;
    }

    $giftRepository = getGiftRepository();

    $giftRepository->updateGift($objectId, array('gifted_by' => $_SESSION['user']['id']));

    if ('' === $friendId) {
        header('Location: ../index.php');
    } else {
        header('Location: ../index.php?user='.$friendId);
    }
