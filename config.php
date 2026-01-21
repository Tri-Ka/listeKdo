<?php
    // error_reporting(E_ALL ^ E_DEPRECATED);
    // session_destroy();
    if (!isset($_SESSION)) {
        session_start();
    }

    $env = getenv('LSTKDO_ENV');

    if ($env === 'docker') {
        $_host_env = getenv('MYSQL_HOST');
        $_db_env = getenv('MYSQL_DATABASE');
        $_user_env = getenv('MYSQL_USER');
        $_pass_env = getenv('MYSQL_PASSWORD');

        $_host = $_host_env ? $_host_env : 'db';
        $_db = $_db_env ? $_db_env : 'listekdo';
        $_username = $_user_env ? $_user_env : 'listekdo_user';
        $_pass = $_pass_env ? $_pass_env : 'listekdo_pass';
    } else {
        $_host = 'sql.free.fr';
        $_db = 'datcharrye';
        $_username = 'datcharrye';
        $_pass = 'spx728';
        // $_host = 'localhost';
        // $_db = 'datcharrye';
        // $_username = 'root';
        // $_pass = 'root';
    }

    $configDir = dirname(__FILE__);

    require_once $configDir . '/lib/Database.php';
    require_once $configDir . '/lib/Repository.php';
    require_once $configDir . '/lib/Entity/User.php';
    require_once $configDir . '/lib/Entity/Gift.php';
    require_once $configDir . '/lib/Entity/Notification.php';
    require_once $configDir . '/lib/Entity/Comment.php';
    require_once $configDir . '/lib/Entity/Reaction.php';
    require_once $configDir . '/lib/UserRepository.php';
    require_once $configDir . '/lib/GiftRepository.php';
    require_once $configDir . '/lib/NotificationRepository.php';
    require_once $configDir . '/lib/CommentRepository.php';
    require_once $configDir . '/lib/ReactionRepository.php';

    $database = new Database($_host, $_username, $_pass, $_db);
    $database->connect();
    $GLOBALS['database'] =& $database;

    $userRepository = new UserRepository($database);
    $giftRepository = new GiftRepository($database);
    $notificationRepository = new NotificationRepository($database);
    $commentRepository = new CommentRepository($database);
    $reactionRepository = new ReactionRepository($database);

    $GLOBALS['userRepository'] =& $userRepository;
    $GLOBALS['giftRepository'] =& $giftRepository;
    $GLOBALS['notificationRepository'] =& $notificationRepository;
    $GLOBALS['commentRepository'] =& $commentRepository;
    $GLOBALS['reactionRepository'] =& $reactionRepository;

    if (!function_exists('getUserRepository')) {
        function getUserRepository()
        {
            return $GLOBALS['userRepository'];
        }
    }

    if (!function_exists('getGiftRepository')) {
        function getGiftRepository()
        {
            return $GLOBALS['giftRepository'];
        }
    }

    if (!function_exists('getNotificationRepository')) {
        function getNotificationRepository()
        {
            return $GLOBALS['notificationRepository'];
        }
    }

    if (!function_exists('getCommentRepository')) {
        function getCommentRepository()
        {
            return $GLOBALS['commentRepository'];
        }
    }

    if (!function_exists('getReactionRepository')) {
        function getReactionRepository()
        {
            return $GLOBALS['reactionRepository'];
        }
    }
