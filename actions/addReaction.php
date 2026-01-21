<?php
include '../config.php';

$reactionRepository = getReactionRepository();
$notificationRepository = getNotificationRepository();
$userRepository = getUserRepository();

$object = $_GET['object'];
$value = $_GET['value'];
$userId = $_SESSION['user']['id'];

$reactionRepository->deleteByProductAndUser($object, $userId);
$notificationRepository->deleteByProductAuthorAndType($object, $userId, 3);

$reactionRepository->createReaction(array(
    'product_id' => $object,
    'user_id' => $userId,
    'type' => $value,
));

$notificationRepository->createNotification(array(
    'author_id' => $userId,
    'product_id' => $object,
    'type' => '3',
    'created_at' => date('Y-m-d H:i:s')
));

$reactions = $reactionRepository->findByProductId($object);
$dbReactions = array();

foreach ($reactions as $reaction) {
    $reactionData = $reaction->toArray();
    $user = $userRepository->findById($reaction->user_id);

    if ($user) {
        $reactionData['user'] = $user->toArray();
    }

    $dbReactions[] = $reactionData;
}

$objectId = $object;
$object = array();

$object['id'] = $objectId;
$object['reactions'] = array();

foreach($dbReactions as $reaction) {
    $object['reactions'][$reaction['type']][] = $reaction;
}

function getReactionUsers($reactions) {
    $users = '';
    foreach ($reactions as $react) {
        $users .= $react['user']['nom']. ', ';
    }

    return substr(trim($users), 0, -1);
}

?>

<a href="#" data-reaction-list class="reaction-list">
    <?php foreach($object['reactions'] as $k => $reaction): ?>
        <div class="reaction" data-toggle="tooltip" data-original-title="<?php echo getReactionUsers($object['reactions'][$k]); ?>" data-placement="bottom">
            <img src="img/reaction/<?php echo $k; ?>.png" alt="">
            <span><?php echo count($object['reactions'][$k]); ?></span>
        </div>
    <?php endforeach; ?>

    <?php if (isset($_SESSION['user'])): ?>
        <?php if (0 === count($object['reactions'])): ?>
            <div class="reaction react-grey">
                <img src="img/reaction/2.png" alt="">
            </div>
        <?php endif; ?>
    <?php endif; ?>
</a>

<?php
    $react = array(
        1 => "j'adore",
        2 => "j'aime",
        3 => "HaHa!",
        4 => "meh",
        5 => "j'aime pas",
        6 => "BEEAARRGH!!!"
    );
?>

<?php if (isset($_SESSION['user'])): ?>
    <div data-reaction-detail class="reaction-details">
        <ul class="reaction-choices">
            <?php for ($i = 1; $i < 7; $i++): ?>
                <li>
                    <a data-toggle="tooltip" data-original-title="<?php echo $react[$i]; ?>" data-add-reaction href="actions/addReaction.php?object=<?php echo $object['id']; ?>&value=<?php echo $i; ?>">
                        <img src="img/reaction/<?php echo $i; ?>.png" alt="">
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </div>
<?php endif; ?>
