<?php
include '../config.php';

$giftRepository = getGiftRepository();

$id = $_POST['object_id'];
$nom = $_POST['nom'];
$description = $_POST['description'];
$imageUrl = $_POST['image'];
$link = $_POST['link'];
$file = $_FILES['file'];
$fileName = null;

if (null != $file['name']) {
    $fileSize = $_FILES['file']['size'];
    $fileSize = round($fileSize / 1024 / 1024, 1);

    if (3 < $fileSize) {
        $_SESSION['error'] = 'L\'image ne doit pas dépasser 3Mo';
        header('Location: ../index.php');
        exit;
    }

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES['file']['tmp_name']);

    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $_SESSION['error'] = 'Le fichier n\'est pas une image';
        header('Location: ../index.php');
        exit;
    }

    $fileName = $_FILES['file']['name'];

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

    $target_file = $target_dir.basename($_FILES['file']['name']);
    $uploadOk = 1;
    $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

    if ($uploadOk) {
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $pictureFile = $_FILES['file']['name'];
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

header('Location: ../index.php?user='.$_SESSION['user']['code']);
