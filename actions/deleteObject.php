<?php
    include '../config.php';

    if (!isset($_SESSION['user']['id'])) {
        header('Location: ../index.php');
        exit;
    }

    $giftRepository = getGiftRepository();
    $notificationRepository = getNotificationRepository();

    $objectId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if (0 === $objectId) {
        header('Location: ../index.php');
        exit;
    }

    $notificationRepository->deleteByProduct($objectId);
    $giftRepository->deleteGift($objectId, $_SESSION['user']['id']);

    header('Location: ../index.php');
    exit;
