<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();
$me = require_login();

list($object, $owner) = managed_object($me, input_int('id'));
object_delete($object);

succeed(array(), list_url($owner['code']), 'Idée supprimée.');
