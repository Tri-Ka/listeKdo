<?php
include '../config.php';

$commentRepository = getCommentRepository();
$notificationRepository = getNotificationRepository();

$content = $_POST['content'];
$productId = $_POST['productId'];
$userId = $_SESSION['user']['id'];
$commentId = null;

if ('' !== $content && '' !== $userId) {
    $comment = $commentRepository->createComment(array(
        'content' => $content,
        'product_id' => $productId,
        'user_id' => $userId,
        'created_at' => date('Y-m-d H:i:s'),
    ));

    if ($comment) {
        $commentId = $comment->id;
    }

    $notificationRepository->createNotification(array(
        'author_id' => $userId,
        'product_id' => $productId,
        'type' => '1',
        'created_at' => date('Y-m-d H:i:s')
    ));
}

$content = str_replace('\"', '"', str_replace("\'", "'", $content));

?>

<li class="comment" data-comment>
    <div class="comment-avatar" style="background-image: url('uploads/<?php echo $_SESSION['user']['id'] .'/'. $_SESSION['user']['pictureFile']; ?>')"></div>

    <div class="comment-content">
        <span class="comment-user"><?php echo $_SESSION['user']['nom']; ?></span> <?php echo nl2br($content); ?>

        <a href="actions/deleteComment.php?id=<?php echo $commentId; ?>" class="delete-comment" data-delete-comment data-toggle="tooltip" data-original-title="supprimer mon commentaire">
            <i class="fa fa-trash"></i>
        </a>
    </div>
</li>
