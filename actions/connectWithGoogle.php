<?php 

include '../config.php';

$id = $_POST['id'];
$name = $_POST['name'];
$imageUrl = $_POST['imageUrl'];
$email = $_POST['email'];            

$userRepository = getUserRepository();

if (null != $id) {
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
