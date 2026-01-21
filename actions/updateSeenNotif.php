<?php
include '../config.php';

if (!isset($_SESSION['user']['id'])) {
	return false;
}

$date = date('Y-m-d H:i:s');
$userRepository = getUserRepository();

$userRepository->updateUser($_SESSION['user']['id'], array('last_seen_notif' => $date));
$_SESSION['user']['last_seen_notif'] = $date;

return true;
