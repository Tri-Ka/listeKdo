<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

logout();
redirect('../index.php');
