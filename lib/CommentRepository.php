<?php

require_once dirname(__FILE__) . '/Repository.php';
require_once dirname(__FILE__) . '/Entity/Comment.php';

class CommentRepository extends Repository
{

    function hydrate($row)
    {
        if (!$row) {
            return null;
        }

        return new Comment($row);
    }

    function findByProductId($productId)
    {
        $escaped = $this->database->escapeString($productId);
        $sql = "SELECT * FROM comment WHERE product_id = '" . $escaped . "'";
        $rows = $this->fetchAll($sql);

        $comments = array();
        foreach ($rows as $row) {
            $comments[] = $this->hydrate($row);
        }

        return $comments;
    }

    function createComment($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $this->database->insert('comment', $data);
        $id = $this->database->getInsertId();

        return $this->hydrate($this->fetchOne("SELECT * FROM comment WHERE id = '" . $this->database->escapeString($id) . "'"));
    }
    
    function deleteByIdAndUser($id, $userId)
    {
        $this->database->delete('comment', array(
            'id' => $id,
            'user_id' => $userId,
        ));

        return true;
    }
}
