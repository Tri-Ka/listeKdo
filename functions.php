<?php
    include 'config.php';

    $userRepository = getUserRepository();
    $giftRepository = getGiftRepository();
    $commentRepository = getCommentRepository();
    $reactionRepository = getReactionRepository();

    if (!function_exists('normalize_view_string')) {
        function normalize_view_string($value)
        {
            if (null === $value) {
                return '';
            }

            $normalized = str_replace('\"', '"', str_replace("\'", "'", $value));

            return $normalized;
        }
    }

    if (!function_exists('build_user_view')) {
        function build_user_view($userData)
        {
            if (!$userData) {
                return null;
            }

            if (is_object($userData)) {
                if (method_exists($userData, 'toArray')) {
                    $userData = $userData->toArray();
                } else {
                    return $userData;
                }
            }

            if (!is_array($userData)) {
                return null;
            }

            $userView = new stdClass();

            foreach ($userData as $key => $value) {
                $userView->$key = $value;
            }

            return $userView;
        }
    }

    if (!function_exists('build_comment_view')) {
        function build_comment_view(&$comment, &$userRepository)
        {
            if (!$comment) {
                return null;
            }

            $commentView = new stdClass();
            $commentView->entity =& $comment;
            $commentView->id = $comment->id;

            $commentData = $comment->toArray();
            $content = '';
            if (isset($commentData['content'])) {
                $content = normalize_view_string($commentData['content']);
            }
            $commentView->content = $content;

            $commentUser = $userRepository->findById($comment->user_id);
            if ($commentUser) {
                $commentView->user = build_user_view($commentUser->toArray());
            } else {
                $commentView->user = null;
            }

            return $commentView;
        }
    }

    if (!function_exists('build_reaction_view')) {
        function build_reaction_view(&$reaction, &$userRepository)
        {
            if (!$reaction) {
                return null;
            }

            $reactionView = new stdClass();
            $reactionView->entity =& $reaction;
            $reactionView->id = $reaction->id;
            $reactionView->type = $reaction->type;

            $reactionUser = $userRepository->findById($reaction->user_id);
            if ($reactionUser) {
                $reactionView->user = build_user_view($reactionUser->toArray());
            } else {
                $reactionView->user = null;
            }

            return $reactionView;
        }
    }

    if (!function_exists('build_reaction_user_names')) {
        function build_reaction_user_names($reactions)
        {
            if (!is_array($reactions)) {
                return '';
            }

            $users = array();

            foreach ($reactions as $reaction) {
                if (!isset($reaction->user) || !is_object($reaction->user)) {
                    continue;
                }

                if (!isset($reaction->user->nom)) {
                    continue;
                }

                $users[] = $reaction->user->nom;
            }

            if (0 === count($users)) {
                return '';
            }

            return implode(', ', $users);
        }
    }

    if (!function_exists('build_gift_view')) {
        function build_gift_view(&$gift, &$userRepository, &$commentRepository, &$reactionRepository)
        {
            if (!$gift) {
                return null;
            }

            $giftView = new stdClass();
            $giftView->entity =& $gift;
            $giftView->id = $gift->id;
            $giftView->user_id = $gift->user_id;

            $giftData = $gift->toArray();

            $giftView->nom = normalize_view_string(isset($giftData['nom']) ? $giftData['nom'] : '');

            $description = normalize_view_string(isset($giftData['description']) ? $giftData['description'] : '');
            $description = trim($description);
            $giftView->description = $description;
            $giftView->has_description = ('' !== $description);
            $giftView->short_description = substr($description, 0, 300);
            $giftView->is_description_truncated = (strlen($description) > 300);

            $imageUrl = '';
            if (isset($giftData['image_url']) && '' !== $giftData['image_url']) {
                $imageUrl = $giftData['image_url'];
            }

            if (isset($giftData['file']) && null !== $giftData['file'] && '' !== $giftData['file']) {
                $imageUrl = 'uploads/img/' . $giftData['file'];
            }

            $giftView->file = isset($giftData['file']) ? $giftData['file'] : null;
            $giftView->image_url = $imageUrl;
            $giftView->has_custom_image = ('' !== $imageUrl && null !== $giftView->file);

            $giftView->link = isset($giftData['link']) ? $giftData['link'] : '';

            $giftView->gifted_by = $gift->gifted_by;
            $giftView->gifted_by_datas = null;

            if (null !== $gift->gifted_by) {
                $giftedUser = $userRepository->findById($gift->gifted_by);
                if ($giftedUser) {
                    $giftView->gifted_by_datas = build_user_view($giftedUser->toArray());
                }
            }

            $giftView->comments = array();
            $comments = $commentRepository->findByProductId($gift->id);
            foreach ($comments as $comment) {
                $commentView = build_comment_view($comment, $userRepository);
                if ($commentView) {
                    $giftView->comments[] = $commentView;
                }
            }

            $giftView->reactions = array();
            $reactions = $reactionRepository->findByProductId($gift->id);
            foreach ($reactions as $reaction) {
                $reactionView = build_reaction_view($reaction, $userRepository);
                if (!$reactionView) {
                    continue;
                }

                $reactionType = $reactionView->type;
                if (!isset($giftView->reactions[$reactionType])) {
                    $giftView->reactions[$reactionType] = array();
                }

                $giftView->reactions[$reactionType][] = $reactionView;
            }

            $giftView->reactions_count = count($giftView->reactions);

            return $giftView;
        }
    }

    if (!function_exists('build_viewer_context')) {
        function build_viewer_context($sessionUser, $currentUser)
        {
            $reactionLabels = array(
                1 => "j'adore",
                2 => "j'aime",
                3 => 'HaHa!',
                4 => 'meh',
                5 => "j'aime pas",
                6 => 'BEEAARRGH!!!',
            );

            $sessionUserId = null;
            $sessionUserAvatarStyle = '';
            if (is_array($sessionUser) && isset($sessionUser['id'])) {
                $sessionUserId = $sessionUser['id'];
                $sessionUserAvatarStyle = retrieveAvatarUrl($sessionUserId);
            }

            $currentUserId = (is_array($currentUser) && isset($currentUser['id'])) ? $currentUser['id'] : null;
            $currentUserCode = (is_array($currentUser) && isset($currentUser['code'])) ? $currentUser['code'] : '';

            $isAuthenticated = (null !== $sessionUserId);
            $isOwner = ($isAuthenticated && null !== $currentUserId && $sessionUserId === $currentUserId);
            $isGuest = ($isAuthenticated && null !== $currentUserId && $sessionUserId !== $currentUserId);

            $context = new stdClass();
            $context->reaction_labels = $reactionLabels;
            $context->session_user_id = $sessionUserId;
            $context->session_user_avatar_style = $sessionUserAvatarStyle;
            $context->current_user_id = $currentUserId;
            $context->current_user_code = $currentUserCode;
            $context->is_authenticated = $isAuthenticated;
            $context->is_owner = $isOwner;
            $context->is_guest = $isGuest;

            return $context;
        }
    }

    if (!function_exists('build_gift_template_data')) {
        function build_gift_template_data(&$giftView, $viewerContext)
        {
            if (!is_object($giftView)) {
                return null;
            }

            if (!isset($giftView->id)) {
                return null;
            }

            if (!is_object($viewerContext) && is_array($viewerContext)) {
                $convertedViewer = new stdClass();
                foreach ($viewerContext as $viewerKey => $viewerValue) {
                    $convertedViewer->$viewerKey = $viewerValue;
                }
                $viewerContext = $convertedViewer;
            }

            if (!is_object($viewerContext)) {
                $viewerContext = new stdClass();
            }

            $sessionUserId = isset($viewerContext->session_user_id) ? $viewerContext->session_user_id : null;

            $giftData = new stdClass();
            $giftData->id = $giftView->id;
            $giftData->name = isset($giftView->nom) ? $giftView->nom : '';
            $giftData->entity =& $giftView;

            $imageUrl = (isset($giftView->image_url) && '' !== $giftView->image_url) ? $giftView->image_url : 'img/idea-default.jpg';
            $giftData->image_src = $imageUrl;
            $giftData->has_custom_image = (isset($giftView->has_custom_image) && $giftView->has_custom_image);

            $giftData->has_description = (isset($giftView->has_description) && $giftView->has_description);
            $giftData->short_description = isset($giftView->short_description) ? $giftView->short_description : '';
            $giftData->is_description_truncated = (isset($giftView->is_description_truncated) && $giftView->is_description_truncated);
            $giftData->full_description = isset($giftView->description) ? $giftView->description : '';

            $giftData->link = isset($giftView->link) ? $giftView->link : '';

            $comments = (isset($giftView->comments) && is_array($giftView->comments)) ? $giftView->comments : array();
            $giftData->comments = $comments;
            $giftData->comments_count = count($comments);

            $reactions = (isset($giftView->reactions) && is_array($giftView->reactions)) ? $giftView->reactions : array();
            $giftData->reactions = $reactions;

            $reactionGroups = array();
            foreach ($reactions as $reactionType => $reactionGroup) {
                $group = new stdClass();
                $group->type = $reactionType;
                $group->items = $reactionGroup;
                $group->count = count($reactionGroup);
                $group->tooltip = build_reaction_user_names($reactionGroup);
                $reactionGroups[] = $group;
            }

            $giftData->reaction_groups = $reactionGroups;
            $giftData->has_reactions = (count($reactionGroups) > 0);

            $giftedById = isset($giftView->gifted_by) ? $giftView->gifted_by : null;
            $giftedByUser = (isset($giftView->gifted_by_datas) && is_object($giftView->gifted_by_datas)) ? $giftView->gifted_by_datas : null;

            $giftData->gifted_by_id = $giftedById;
            $giftData->gifted_by_user = $giftedByUser;
            $giftData->gifted_by_name = ($giftedByUser && isset($giftedByUser->nom)) ? $giftedByUser->nom : '';
            $giftData->gifted_by_code = ($giftedByUser && isset($giftedByUser->code)) ? $giftedByUser->code : '';
            $giftData->gifted_by_style = '';
            if ($giftedByUser && isset($giftedByUser->id)) {
                $giftData->gifted_by_style = retrieveAvatarUrl($giftedByUser->id);
            }

            $giftData->is_gifted = (null !== $giftedById);
            $giftData->is_gifted_by_current_user = ($giftData->is_gifted && null !== $giftedById && $giftedById == $sessionUserId);

            $giftData->viewer_can_edit = (isset($viewerContext->is_owner) && $viewerContext->is_owner);
            $giftData->viewer_is_guest = (isset($viewerContext->is_guest) && $viewerContext->is_guest);
            $giftData->viewer_is_authenticated = (isset($viewerContext->is_authenticated) && $viewerContext->is_authenticated);
            $giftData->viewer_session_user_id = $sessionUserId;
            $giftData->viewer_session_user_avatar_style = isset($viewerContext->session_user_avatar_style) ? $viewerContext->session_user_avatar_style : '';
            $giftData->current_user_code = isset($viewerContext->current_user_code) ? $viewerContext->current_user_code : '';

            return $giftData;
        }
    }

    if (!function_exists('build_gift_list_view_model')) {
        function build_gift_list_view_model($sessionUser, $currentUser, $giftViews)
        {
            $viewModel = new stdClass();

            $viewerContext = build_viewer_context($sessionUser, $currentUser);
            $viewModel->viewer = $viewerContext;

            $giftItems = array();

            if (is_array($giftViews)) {
                foreach ($giftViews as $giftView) {
                    if (!is_object($giftView)) {
                        continue;
                    }

                    $giftData = build_gift_template_data($giftView, $viewerContext);

                    if (!is_object($giftData)) {
                        continue;
                    }

                    $giftItems[] = $giftData;
                }
            }

            $viewModel->gifts = $giftItems;
            $viewModel->reaction_labels = isset($viewerContext->reaction_labels) ? $viewerContext->reaction_labels : array();
            $viewModel->session_user = $sessionUser;
            $viewModel->current_user = $currentUser;
            $viewModel->raw_gifts = $giftViews;

            return $viewModel;
        }
    }


    if (isset($_COOKIE['listeKdoUserCode'])) {
        if (retrieveUser($_COOKIE['listeKdoUserCode'])) {
            $_SESSION['user'] = retrieveUser($_COOKIE['listeKdoUserCode']);
            setcookie('listeKdoUserCode', $_SESSION['user']['code'], time()+31556926 ,'/');
        }
    }

    $userId = null;
    $currentUser = null;
    $currentTheme = array(
        'label' => 'noel',
        'title' => 'Liste de Noël',
    );

    $themes = array(
        'noel' => 'Noël',
        'birthday' => 'Anniversaire',
        'naissance' => 'Naissance',
    );

    if (isset($_GET['user'])) {
        $userId = $_GET['user'];
    }

    if (null === $userId && isset($_SESSION['user'])) {
        $userId = $_SESSION['user']['code'];
    }

    if (null !== $userId) {
        $currentUser = retrieveUser($userId);
    }

    if ($currentUser) {
        $currentTheme['label'] = $currentUser['theme'];

        switch ($currentUser['theme']) {
            case 'noel':
                $currentTheme['title'] = 'Liste de Noël';
                break;

            case 'birthday':
                $currentTheme['title'] = 'Liste d\'anniversaire';
                break;

            case 'naissance':
                $currentTheme['title'] = 'Liste de naissance';
                break;

            default:
                break;
        }
    }

    $objects = array();

    if ($currentUser) {
        $gifts = $giftRepository->findByUserId($currentUser['id']);

        foreach ($gifts as $gift) {
            $giftView = build_gift_view($gift, $userRepository, $commentRepository, $reactionRepository);

            if ($giftView) {
                $objects[$gift->id] = $giftView;
            }
        }
    }

    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $_SESSION['user']['friends'] = retrieveFriends($_SESSION['user']['id']);
        $_SESSION['user']['notifications'] = retrieveNotifications($_SESSION['user']);
    } else {
        unset($_SESSION['user']);
    }

    $_SESSION['currentUserCode'] = $userId;

    function retrieveNotifications($user)
    {
        if (!is_array($user)) {
            return array();
        }

        $notifications = array();

        if (!isset($user['friends']) || !is_array($user['friends']) || 0 === count($user['friends'])) {
            return array();
        }

        $userRepository = getUserRepository();
        $giftRepository = getGiftRepository();
        $notificationRepository = getNotificationRepository();

        $freshUser = $userRepository->findById($user['id']);

        if ($freshUser) {
            $_SESSION['user']['last_seen_notif'] = $freshUser->last_seen_notif;
        }

        $friendUserIds = array();
        foreach ($user['friends'] as $friend) {
            $friendUserIds[] = $friend['id'];
        }

        $productUserIds = array();
        $userGifts = $giftRepository->findByUserId($user['id']);
        foreach ($userGifts as $gift) {
            $productUserIds[] = $gift->id;
        }

        $productFriendIds = array();
        foreach ($friendUserIds as $friendId) {
            $friendGifts = $giftRepository->findByUserId($friendId);
            foreach ($friendGifts as $gift) {
                $productFriendIds[] = $gift->id;
            }
        }

        $feed = $notificationRepository->findFeedForUser($user['id'], $friendUserIds, $productUserIds, $productFriendIds);

        foreach ($feed as $notification) {
            $data = $notification->toArray();

            $author = $userRepository->findById($notification->author_id);
            if ($author) {
                $data['user'] = $author->toArray();
            }

            $product = $giftRepository->findById($notification->product_id);
            if ($product) {
                $data['product'] = $product->toArray();

                $productOwner = $userRepository->findById($product->user_id);
                if ($productOwner) {
                    $data['product_user'] = $productOwner->toArray();
                }
            }

            $data['new'] = false;
            if (!isset($_SESSION['user']['last_seen_notif']) || null === $_SESSION['user']['last_seen_notif']) {
                $data['new'] = true;
            } elseif (strtotime($notification->created_at) > strtotime($_SESSION['user']['last_seen_notif'])) {
                $data['new'] = true;
            }

            $diff = cc2_date_diff(date('Y-m-d H:i:s'), $notification->created_at);

            if (0 !== $diff['d']) {
                $data['timePassed'] = $diff['d'].' j';
            } elseif (0 !== $diff['h']) {
                $data['timePassed'] = $diff['h'].' h';
            } elseif (0 !== $diff['m']) {
                $data['timePassed'] = $diff['m'].' min';
            } elseif (0 !== $diff['s']) {
                $data['timePassed'] = $diff['s'].' sec';
            }

            $notifications[] = $data;
        }

        return $notifications;
    }
    function retrieveUser($code = null, $id = null)
    {
        $repository = getUserRepository();
        $user = null;

        if (null !== $code) {
            $user = $repository->findByCode($code);
        }

        if (null !== $id && null === $user) {
            $user = $repository->findById($id);
        }

        if ($user) {
            return $user->toArray();
        }

        return null;
    }

    function retrieveFriends($userId)
    {
        $repository = getUserRepository();
        $friends = $repository->findFriendsByUserId($userId);
        $results = array();

        foreach ($friends as $friend) {
            $results[] = $friend->toArray();
        }

        return $results;
    }

    function auto_version($file)
    {
        if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/listeKdo/'.$file)) {
            return $file;
        }

        $mtime = filemtime($_SERVER['DOCUMENT_ROOT'].'/listeKdo/'.$file);

        return $file.'?v='.$mtime;
    }

    function cc2_date_diff($start, $end)
    {
        $sdate = strtotime($start);
        $edate = strtotime($end);

        if ($edate < $sdate) {
            $sdate_temp = $sdate;
            $sdate = $edate;
            $edate = $sdate_temp;
        }
        $time = $edate - $sdate;
        $preday[0] = 0;

        $diff = array(
            's' => 0,
            'm' => 0,
            'h' => 0,
            'd' => 0,
        );

        if ($time>=0 && $time<=59) {
            // Seconds
            $timeshift = $time.' seconds ';
            $diff['s'] = $time;
        } elseif ($time>=60 && $time<=3599) {
            // Minutes + Seconds
            $pmin = ($edate - $sdate) / 60;
            $premin = explode('.', $pmin);

            $presec = $pmin-$premin[0];
            $sec = $presec*60;

            $timeshift = $premin[0].' min '.round($sec, 0).' sec ';
            $diff['m'] = $premin[0];
            $diff['s'] = round($sec, 0);
        } elseif ($time>=3600 && $time<=86399) {
            // Hours + Minutes
            $phour = ($edate - $sdate) / 3600;
            $prehour = explode('.', $phour);

            $premin = $phour-$prehour[0];
            $min = explode('.', $premin*60);

            if (!isset($min[1])) {
                $min[1] = 0;
            }

            $presec = '0.'.$min[1];
            $sec = $presec*60;

            $timeshift = $prehour[0].' hrs '.$min[0].' min '.round($sec, 0).' sec ';
            $diff['h'] = $prehour[0];
            $diff['m'] = $min[0];
            $diff['s'] = round($sec, 0);
        } elseif ($time>=86400) {
            // Days + Hours + Minutes
            $pday = ($edate - $sdate) / 86400;
            $preday = explode('.', $pday);

            $phour = $pday-$preday[0];
            $prehour = explode('.', $phour*24);

            $premin = ($phour*24)-$prehour[0];
            $min = explode('.', $premin*60);

            if (!isset($min[1])) {
                $min[1] = 0;
            }

            $presec = '0.'.$min[1];
            $sec = $presec*60;

            $timeshift = $preday[0].' days '.$prehour[0].' hrs '.$min[0].' min '.round($sec, 0).' sec ';
            $diff['d'] = $preday[0];
            $diff['h'] = $prehour[0];
            $diff['m'] = $min[0];
            $diff['s'] = round($sec, 0);
        }

        return $diff;
    }


    function retrieveAvatarUrl($userId = null, $userCode = null)
    {
        $user = retrieveUser($userCode, $userId);

        if (null !== $user['pictureFileUrl'] && null == $user['pictureFile']) {
            $pictureUrl = $user['pictureFileUrl'];
        } else {
            $pictureUrl = 'uploads/'.$user['id'].'/'.$user['pictureFile'];
        }

        return "background-image:url(".$pictureUrl.")";
    }

    if (!function_exists('render_template')) {
        function render_template($relativePath, $variables = array())
        {
            $basePath = dirname(__FILE__) . '/templates/';
            $cleanPath = ltrim($relativePath, '/');
            $fullPath = $basePath . $cleanPath;

            if (!file_exists($fullPath)) {
                return;
            }

            if (is_array($variables) && 0 < count($variables)) {
                extract($variables, EXTR_SKIP);
            }

            include $fullPath;
        }
    }
