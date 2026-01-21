<?php

require_once dirname(__FILE__) . '/Repository.php';
require_once dirname(__FILE__) . '/Entity/User.php';

class UserRepository extends Repository
{
    function UserRepository(&$database)
    {
        $this->Repository($database);
    }

    function hydrate($row)
    {
        if (!$row) {
            return null;
        }

        return new User($row);
    }

    function findById($id)
    {
        $escapedId = $this->database->escapeString($id);
        $sql = "SELECT * FROM liste_user WHERE id = '" . $escapedId . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function findByCode($code)
    {
        $escaped = $this->database->escapeString($code);
        $sql = "SELECT * FROM liste_user WHERE code = '" . $escaped . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function findByName($name)
    {
        $escaped = $this->database->escapeString($name);
        $sql = "SELECT * FROM liste_user WHERE nom = '" . $escaped . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function findByGoogleId($googleId)
    {
        $escaped = $this->database->escapeString($googleId);
        $sql = "SELECT * FROM liste_user WHERE googleId = '" . $escaped . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $this->hydrate($row);
    }

    function findFriendsByUserId($userId)
    {
        $escaped = $this->database->escapeString($userId);
        $sql = "SELECT * FROM liste_user WHERE liste_user.code IN (" .
            "SELECT user_friend.friend_code FROM user_friend WHERE user_friend.user_id = '" . $escaped . "'" .
            ") ORDER BY liste_user.nom ASC";

        $rows = $this->fetchAll($sql);
        $friends = array();

        foreach ($rows as $row) {
            $friends[] = $this->hydrate($row);
        }

        return $friends;
    }

    function createUser($name, $password, $theme, $pictureFile)
    {
        $code = md5($name . $password);
        $data = array(
            'nom' => $name,
            'code' => $code,
            'password' => md5($password),
            'theme' => $theme,
            'pictureFile' => $pictureFile,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->database->insert('liste_user', $data);
        $id = $this->database->getInsertId();

        return $this->findById($id);
    }

    function updateUser($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->database->update('liste_user', $data, array('id' => $id));

        return $this->findById($id);
    }

    function createGoogleUser($googleId, $name, $pictureFileUrl)
    {
        $code = md5($googleId);
        $data = array(
            'nom' => $name,
            'code' => $code,
            'theme' => 'noel',
            'googleId' => $googleId,
            'pictureFileUrl' => $pictureFileUrl,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->database->insert('liste_user', $data);
        $id = $this->database->getInsertId();

        return $this->findById($id);
    }

    function addFriendRelation($userId, $friendCode)
    {
        if ($this->hasFriendRelation($userId, $friendCode)) {
            return true;
        }

        $this->database->insert('user_friend', array(
            'user_id' => $userId,
            'friend_code' => $friendCode,
        ));

        return true;
    }

    function removeFriendRelation($userId, $friendCode)
    {
        $this->database->delete('user_friend', array(
            'user_id' => $userId,
            'friend_code' => $friendCode,
        ));

        return true;
    }

    function hasFriendRelation($userId, $friendCode)
    {
        $userEscaped = $this->database->escapeString($userId);
        $codeEscaped = $this->database->escapeString($friendCode);
        $sql = "SELECT id FROM user_friend WHERE user_id = '" . $userEscaped . "' AND friend_code = '" . $codeEscaped . "' LIMIT 1";
        $row = $this->fetchOne($sql);

        return $row ? true : false;
    }
}
