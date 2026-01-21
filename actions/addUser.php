<?php

include '../config.php';

if (empty($_POST)) {
    $_SESSION['error'] = 'L\'image ne doit pas dépasser 2Mo';
    header('Location: ../index.php');
    exit;
}

$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rePassword = isset($_POST['re-password']) ? $_POST['re-password'] : '';
$userRepository = getUserRepository();

if ($userRepository->findByName($nom)) {
    $_SESSION['error'] = 'Ce nom existe déjà';
    header('Location: ../index.php');
    exit;
}

if (isset($_FILES['pictureFile']) && isset($_FILES['pictureFile']['size']) && $_FILES['pictureFile']['size']) {
    $fileSize = $_FILES['pictureFile']['size'];
    $fileSize = round($fileSize / 1024 / 1024, 1);

    if (2 < $fileSize) {
        $_SESSION['error'] = 'L\'image ne doit pas dépasser 2Mo';
        header('Location: ../index.php');
        exit;
    }
}

if ($password !== $rePassword) {
    $_SESSION['error'] = 'les mots de passes sont différents';
    header('Location: ../index.php');
    exit;
}

if ('' !== trim($nom) && '' !== trim($password)) {
    $filename = '';

    if (isset($_FILES['pictureFile']) && isset($_FILES['pictureFile']['tmp_name']) && $_FILES['pictureFile']['tmp_name']) {
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES['pictureFile']['tmp_name']);

        if ($check !== false) {
            $uploadOk = 1;
            $filename = encryptFileName($_FILES['pictureFile']['name']);
        } else {
            $_SESSION['error'] = 'Le fichier n\'est pas une image';
            header('Location: ../index.php');
            exit;
        }
    }

    $user = $userRepository->createUser($nom, $password, 'noel', $filename);

    if (!is_object($user)) {
        $user = $userRepository->findByName($nom);
    }

    if (!is_object($user)) {
        $_SESSION['error'] = 'Impossible de créer l\'utilisateur, veuillez réessayer plus tard';
        header('Location: ../index.php');
        exit;
    }

    $_SESSION['user'] = $user->toArray();

    $target_dir = '../uploads/' . $user->id . '/';
    $uploads_root = dirname($target_dir);

    if (!is_dir($uploads_root) && !mkdir($uploads_root, 0777)) {
        $_SESSION['error'] = 'Impossible de creer le dossier d\'upload';
        header('Location: ../index.php');
        exit;
    }

    if (!is_dir($target_dir) && !mkdir($target_dir, 0777)) {
        $_SESSION['error'] = 'Impossible de creer le dossier utilisateur';
        header('Location: ../index.php');
        exit;
    }

    if ('' !== $filename) {
        $target_file = $target_dir . $filename;
        $uploadOk = 1;
        $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

        if ($uploadOk) {
            if (move_uploaded_file($_FILES['pictureFile']['tmp_name'], $target_file)) {
                // correctImageOrientation($target_file);
                $pictureFile = $_FILES['pictureFile']['name'];
            } else {
                $_SESSION['error'] = 'erreur lors de l\'upload';
                header('Location: ../index.php');
                exit;
            }
        }
    }

    header('Location: ../index.php?user=' . $user->code);
    exit;
} else {
    $_SESSION['error'] = 'des champs requis ne sont pas renseignés';
    header('Location: ../index.php');
    exit;
}

function correctImageOrientation($filename)
{
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
    $file_name = $file_info['filename'];
    $file_extension = $file_info['extension'];

    // Encryption key (replace with your own key)
    $key = 'encrypt_key';

    // Encrypt the file name using AES encryption
    $encrypted_file_name = md5($file_name);

    // Concatenate encrypted file name with the original extension
    return $encrypted_file_name . '.' . $file_extension;
}
