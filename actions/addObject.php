<?php
include '../config.php';

$giftRepository = getGiftRepository();
$notificationRepository = getNotificationRepository();

$nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
$imageUrl = isset($_POST['image']) ? trim((string) $_POST['image']) : '';
$link = isset($_POST['link']) ? trim((string) $_POST['link']) : '';
$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0);
$file = isset($_FILES['file']) && is_array($_FILES['file']) ? $_FILES['file'] : array();
$fileName = null;

if (0 === $userId) {
    $_SESSION['error'] = 'Utilisateur invalide';
    header('Location: ../index.php');
    exit;
}

if (!empty($file) && isset($file['name']) && '' !== $file['name']) {
    $fileSize = isset($file['size']) ? (int) $file['size'] : 0;
    $fileSize = round($fileSize / 1024 / 1024, 1);

    if (3 < $fileSize) {
        $_SESSION['error'] = 'L\'image ne doit pas dépasser 3Mo';
        header('Location: ../index.php');
        exit;
    }

    // Check if image file is a actual image or fake image
    if (!isset($file['tmp_name']) || '' === $file['tmp_name']) {
        $_SESSION['error'] = 'Le fichier uploadé est invalide';
        header('Location: ../index.php');
        exit;
    }

    $check = getimagesize($file['tmp_name']);

    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $_SESSION['error'] = 'Le fichier n\'est pas une image';
        header('Location: ../index.php');
        exit;
    }

    $fileName = $file['name'];

    $target_dir = '../uploads/img/';
    $uploads_root = dirname(rtrim($target_dir, '/'));

    if (!is_dir($uploads_root) && !@mkdir($uploads_root, 0777, true)) {
        $_SESSION['error'] = 'Impossible de creer le dossier d\'upload';
        header('Location: ../index.php');
        exit;
    }

    if (!is_dir($target_dir) && !@mkdir($target_dir, 0777)) {
        $_SESSION['error'] = 'Impossible de creer le dossier image';
        header('Location: ../index.php');
        exit;
    }

    $target_file = $target_dir.basename($file['name']);
    $uploadOk = 1;
    $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

    if ($uploadOk) {
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $pictureFile = $file['name'];
        } else {
            $_SESSION['error'] = 'erreur lors de l\'upload';
            header('Location: ../index.php');
            exit;
        }
    }
}

if ('' !== $nom) {
    $gift = $giftRepository->createGift(array(
        'nom' => $nom,
        'description' => $description,
        'image_url' => $imageUrl,
        'link' => $link,
        'user_id' => $userId,
        'file' => $fileName,
        'created_at' => date('Y-m-d H:i:s')
    ));

    if ($gift) {
        $notificationRepository->createNotification(array(
            'author_id' => $userId,
            'product_id' => $gift->id,
            'type' => '2',
            'created_at' => date('Y-m-d H:i:s')
        ));
    }
}

if (!isset($_SESSION['user']['code'])) {
    header('Location: ../index.php');
    exit;
}

header('Location: ../index.php?user='.$_SESSION['user']['code']);
