<?php
/*
 * « Tout marquer comme lu ».
 */
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

notifications_mark_all_read($me);

succeed(array('unread' => 0));
