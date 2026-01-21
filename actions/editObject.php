<?php
include '../config.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: ../index.php');
    exit;
}

$giftRepository = getGiftRepository();

$id = isset($_POST['object_id']) ? (int) $_POST['object_id'] : 0;
$nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
$imageUrl = isset($_POST['image']) ? trim((string) $_POST['image']) : '';
$link = isset($_POST['link']) ? trim((string) $_POST['link']) : '';
$file = isset($_FILES['file']) && is_array($_FILES['file']) ? $_FILES['file'] : array();
$fileName = null;

if (0 === $id) {
    $_SESSION['error'] = 'Objet invalide';
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
    $giftRepository->updateGift($id, array(
        'nom' => $nom,
        'description' => $description,
        'image_url' => $imageUrl,
        'link' => $link,
        'file' => $fileName,
    ), $_SESSION['user']['id']);
}

if (!isset($_SESSION['user']['code'])) {
    header('Location: ../index.php');
    exit;
}

header('Location: ../index.php?user='.$_SESSION['user']['code']);
