<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';
require_post();

$me = require_login();
if (onboarding_available()) {
    db_update('liste_user', array('onboarding_seen_at' => db_now()), array('id' => (int) $me['id']));
}

succeed();
