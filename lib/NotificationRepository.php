<?php

require_once dirname(__FILE__) . '/Repository.php';
require_once dirname(__FILE__) . '/Entity/Notification.php';

class NotificationRepository extends Repository
{
    function NotificationRepository(&$database)
    {
        $this->Repository($database);
    }

    function hydrate($row)
    {
        if (!$row) {
            return null;
        }

        return new Notification($row);
    }

    function createNotification($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $this->database->insert('notification', $data);

        return true;
    }

    function deleteByProductAuthorAndType($productId, $authorId, $type)
    {
        $this->database->delete('notification', array(
            'product_id' => $productId,
            'author_id' => $authorId,
            'type' => $type,
        ));
    }

    function deleteByProduct($productId)
    {
        $this->database->delete('notification', array(
            'product_id' => $productId,
        ));
    }

    function findFeedForUser($userId, $friendIds, $productUserIds, $productFriendIds)
    {
        $userIdEscaped = $this->database->escapeString($userId);
        $friendList = $this->buildInClause($friendIds);
        $productList = $this->buildInClause($productUserIds);
        $productFriendList = $this->buildInClause($productFriendIds);

        $conditions = array();

        if ('' !== $productList) {
            $conditions[] = "(author_id != '" . $userIdEscaped . "' AND product_id IN (" . $productList . "))";
        }

        if ('' !== $friendList) {
            $friendCondition = "(author_id != '" . $userIdEscaped . "' AND author_id IN (" . $friendList . ") AND type = 2)";
            $conditions[] = $friendCondition;

            if ('' !== $productFriendList) {
                $conditions[] = "(author_id != '" . $userIdEscaped . "' AND author_id IN (" . $friendList . ") AND type = 1 AND product_id IN (" . $productFriendList . "))";
            }
        }

        if (0 === count($conditions)) {
            return array();
        }

        $sql = 'SELECT * FROM notification WHERE ' . implode(' OR ', $conditions) . ' ORDER BY created_at DESC LIMIT 100';
        $rows = $this->fetchAll($sql);

        $notifications = array();
        foreach ($rows as $row) {
            $notifications[] = $this->hydrate($row);
        }

        return $notifications;
    }
}
