<?php
include '../config.php';

$date = date('Y-m-d H:i:s');
$userRepository = getUserRepository();

$userRepository->updateUser($_SESSION['user']['id'], array('last_seen_notif' => $date));
$_SESSION['user']['last_seen_notif'] = $date;

return true;
