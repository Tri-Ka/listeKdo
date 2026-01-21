<?php

require_once dirname(__FILE__) . '/Repository.php';
require_once dirname(__FILE__) . '/Entity/Gift.php';

class GiftRepository extends Repository
{
    function GiftRepository(&$database)
    {
        $this->Repository($database);
    }

    function hydrate($row)
    {
        if (!$row) {
            return null;
        }

        return new Gift($row);
    }

    function findById($id)
    {
        $escaped = $this->database->escapeString($id);
        $sql = "SELECT * FROM liste_noel WHERE id = '" . $escaped . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function findByUserId($userId)
    {
        $escaped = $this->database->escapeString($userId);
        $sql = "SELECT * FROM liste_noel WHERE user_id = '" . $escaped . "' ORDER BY created_at DESC, id DESC";
        $rows = $this->fetchAll($sql);

        $gifts = array();
        foreach ($rows as $row) {
            $gifts[] = $this->hydrate($row);
        }

        return $gifts;
    }

    function createGift($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->database->insert('liste_noel', $data);
        $id = $this->database->getInsertId();

        return $this->findById($id);
    }

    function updateGift($id, $data, $userId = null)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $criteria = array('id' => $id);

        if (null !== $userId) {
            $criteria['user_id'] = $userId;
        }

        $this->database->update('liste_noel', $data, $criteria);

        return $this->findById($id);
    }

    function deleteGift($id, $userId)
    {
        $this->database->delete('liste_noel', array(
            'id' => $id,
            'user_id' => $userId,
        ));

        return true;
    }
}
