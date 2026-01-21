<?php 

include '../config.php';

$id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
$name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
$imageUrl = isset($_POST['imageUrl']) ? trim((string) $_POST['imageUrl']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';           

$userRepository = getUserRepository();

if ('' !== $id) {
    $user = $userRepository->findByGoogleId($id);

    if (!$user) {
        $user = $userRepository->createGoogleUser($id, $name, $imageUrl);
    }

    if ($user) {
        $_SESSION['user'] = $user->toArray();
        setcookie('listeKdoUserCode', $user->code, time()+31556926 ,'/');
    }
}

return;
