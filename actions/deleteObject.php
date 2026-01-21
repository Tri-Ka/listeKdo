<?php
    include '../config.php';

    $giftRepository = getGiftRepository();
    $notificationRepository = getNotificationRepository();

    $objectId = $_GET['id'];

    $notificationRepository->deleteByProduct($objectId);
    $giftRepository->deleteGift($objectId, $_SESSION['user']['id']);

    header('Location: ../index.php');
    exit;
