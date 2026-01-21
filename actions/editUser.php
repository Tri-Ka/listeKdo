<?php

include '../config.php';

$userRepository = getUserRepository();

$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rePassword = isset($_POST['re-password']) ? $_POST['re-password'] : '';

if ('' === trim($nom)) {
    $_SESSION['error'] = 'Le nom ne peut pas être vide';
    header('Location: ../index.php');
    exit;
}

if (isset($_FILES['pictureFile']) && isset($_FILES['pictureFile']['size']) && 0 < $_FILES['pictureFile']['size']) {
    $fileSize = $_FILES['pictureFile']['size'];
    $fileSize = round($fileSize / 1024 / 1024, 1);

    if (3 < $fileSize) {
        $_SESSION['error'] = 'L\'image ne doit pas dépasser 3Mo';
        header('Location: ../index.php');
        exit;
    }

    // Check if image file is a actual image or fake image
    if (!isset($_FILES['pictureFile']['tmp_name']) || '' === $_FILES['pictureFile']['tmp_name']) {
        $_SESSION['error'] = 'Le fichier uploadé est invalide';
        header('Location: ../index.php');
        exit;
    }

    $check = getimagesize($_FILES['pictureFile']['tmp_name']);

    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $_SESSION['error'] = 'Les fichier n\'est pas une image';
        header('Location: ../index.php');
        exit;
    }

    $target_dir = '../uploads/' . $_SESSION['user']['id'] . '/';
    $uploads_root = dirname($target_dir);

    if (!is_dir($uploads_root) && !@mkdir($uploads_root, 0777, true)) {
        $_SESSION['error'] = 'Impossible de creer le dossier d\'upload';
        header('Location: ../index.php');
        exit;
    }

    if (!is_dir($target_dir) && !@mkdir($target_dir, 0777)) {
        $_SESSION['error'] = 'Impossible de creer le dossier utilisateur';
        header('Location: ../index.php');
        exit;
    }

    $filename = encryptFileName($_FILES['pictureFile']['name']);
    $target_file = $target_dir . $filename;
    $uploadOk = 1;
    $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

    if ($uploadOk) {
        if (move_uploaded_file($_FILES['pictureFile']['tmp_name'], $target_file)) {
            correctImageOrientation($target_file);

        } else {
            $_SESSION['error'] = 'erreur lors de l\'upload';
            header('Location: ../index.php');
            exit;
        }
    }
}

if ($password && $password !== $rePassword) {
    $_SESSION['error'] = 'les mots de passes sont différents';
    header('Location: ../index.php');
    exit;
}

$existingUserCode = isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['code']) ? $_SESSION['user']['code'] : '';

$userInfos = array();
$userInfos['nom'] = $nom;

if ($password) {
    $userInfos['password'] = md5($password);
}

if (isset($_FILES['pictureFile']) && isset($_FILES['pictureFile']['size']) && 0 < $_FILES['pictureFile']['size']) {
    $userInfos['pictureFile'] = $filename;
}

$user = $userRepository->updateUser($_SESSION['user']['id'], $userInfos);

if (!is_object($user)) {
    $user = $userRepository->findById($_SESSION['user']['id']);
}

if (!is_object($user)) {
    $_SESSION['error'] = 'Impossible de mettre à jour votre profil pour le moment';
    header('Location: ../index.php');
    exit;
}

if ((!isset($user->code) || '' === $user->code) && '' !== $existingUserCode) {
    $user->code = $existingUserCode;
}

$_SESSION['user'] = $user->toArray();

$redirectCode = isset($user->code) ? $user->code : '';
if ('' === $redirectCode && isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['code'])) {
    $redirectCode = $_SESSION['user']['code'];
}

if ('' === $redirectCode) {
    header('Location: ../index.php');
} else {
    header('Location: ../index.php?user=' . $redirectCode);
}

function correctImageOrientation($filename)
{
    if (!function_exists('exif_imagetype')) {
        return;
    }

    if (exif_imagetype($filename) === IMAGETYPE_JPEG) {
        if (function_exists('exif_read_data')) {
            $exif = exif_read_data($filename);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];

                if ($orientation != 1) {
                    $img = imagecreatefromjpeg($filename);
                    $deg = 0;

                    switch ($orientation) {
                        case 3:
                            $deg = 180;
                            break;
                        case 6:
                            $deg = 270;
                            break;
                        case 8:
                            $deg = 90;
                            break;
                    }

                    if ($deg) {
                        $img = imagerotate($img, $deg, 0);
                    }

                    // then rewrite the rotated image back to the disk as $filename
                    imagejpeg($img, $filename, 95);
                } // if there is some rotation necessary
            } // if have the exif orientation info
        } // if function exists
    }
}

function encryptFileName($filePath)
{

    // Splitting file name and extension
    $file_info = pathinfo($filePath);
    $file_name = isset($file_info['filename']) ? $file_info['filename'] : '';
    $file_extension = isset($file_info['extension']) ? $file_info['extension'] : '';

    // Encrypt the file name using AES encryption
    $encrypted_file_name = md5($file_name);

    // Concatenate encrypted file name with the original extension
    if ('' === $file_extension) {
        return $encrypted_file_name;
    }

    return $encrypted_file_name . '.' . $file_extension;
}
