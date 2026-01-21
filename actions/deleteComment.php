<?php
    include '../config.php';

    if (!isset($_SESSION['user']['id'])) {
        header('Location: ../index.php');
        exit;
    }

    $commentRepository = getCommentRepository();
    $commentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (0 === $commentId) {
        header('Location: ../index.php');
        exit;
    }

    $commentRepository->deleteByIdAndUser($commentId, $_SESSION['user']['id']);
