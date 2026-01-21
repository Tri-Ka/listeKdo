<?php

require_once dirname(__FILE__) . '/Repository.php';
require_once dirname(__FILE__) . '/Entity/Reaction.php';

class ReactionRepository extends Repository
{

    function hydrate($row)
    {
        if (!$row) {
            return null;
        }

        return new Reaction($row);
    }

    function findByProductId($productId)
    {
        $escaped = $this->database->escapeString($productId);
        $sql = "SELECT * FROM reaction WHERE product_id = '" . $escaped . "'";
        $rows = $this->fetchAll($sql);

        $reactions = array();
        foreach ($rows as $row) {
            $reactions[] = $this->hydrate($row);
        }

        return $reactions;
    }

    function findByProductAndUser($productId, $userId)
    {
        $product = $this->database->escapeString($productId);
        $user = $this->database->escapeString($userId);
        $sql = "SELECT * FROM reaction WHERE product_id = '" . $product . "' AND user_id = '" . $user . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function createReaction($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $this->database->insert('reaction', $data);
        $id = $this->database->getInsertId();

        return $this->findById($id);
    }

    function findById($id)
    {
        $escaped = $this->database->escapeString($id);
        $sql = "SELECT * FROM reaction WHERE id = '" . $escaped . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function deleteByProductAndUser($productId, $userId)
    {
        $this->database->delete('reaction', array(
            'product_id' => $productId,
            'user_id' => $userId,
        ));
    }
}
