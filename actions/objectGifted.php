<?php
    include '../config.php';

    $objectId = $_GET['id'];
    $giftRepository = getGiftRepository();

    $giftRepository->updateGift($objectId, array('gifted_by' => $_SESSION['user']['id']));

    header('Location: ../index.php?user='.$_GET['friendId']);
