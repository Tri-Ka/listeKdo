<?php
    include '../config.php';

    $commentRepository = getCommentRepository();

    $commentRepository->deleteByIdAndUser($_GET['id'], $_SESSION['user']['id']);
