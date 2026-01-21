<?php

class Database
{
    var $host;
    var $user;
    var $password;
    var $dbName;
    var $link;

    function Database($host, $user, $password, $dbName)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->link = null;
    }

    function connect()
    {
        if (null === $this->link) {
            $this->link = mysql_connect($this->host, $this->user, $this->password);
            if (!$this->link) {
                return false;
            }
        }

        if (!mysql_select_db($this->dbName, $this->link)) {
            return false;
        }

        return $this->link;
    }

    function ensureConnection()
    {
        if (null === $this->link) {
            $this->connect();
        }

        return $this->link;
    }

    function escapeString($value)
    {
        $this->ensureConnection();

        return mysql_real_escape_string($value, $this->link);
    }

    function escapeValue($value)
    {
        if (null === $value) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return "'" . $this->escapeString($value) . "'";
        }

        return "'" . $this->escapeString($value) . "'";
    }

    function query($sql)
    {
        $this->ensureConnection();

        return mysql_query($sql, $this->link);
    }

    function fetchAssoc($result)
    {
        return mysql_fetch_assoc($result);
    }

    function insert($table, $data)
    {
        $keys = array_keys($data);
        $escapedValues = array();
        foreach ($data as $value) {
            $escapedValues[] = $this->escapeValue($value);
        }

        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $keys) . '`) VALUES (' . implode(',', $escapedValues) . ')';

        return $this->query($sql);
    }

    function getInsertId()
    {
        $this->ensureConnection();

        return mysql_insert_id($this->link);
    }

    function update($table, $data, $criteria)
    {
        $sets = array();
        foreach ($data as $key => $value) {
            $sets[] = '`' . $key . '` = ' . $this->escapeValue($value);
        }

        $whereParts = array();
        foreach ($criteria as $key => $value) {
            $whereParts[] = '`' . $key . '` = ' . $this->escapeValue($value);
        }

        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $whereParts);

        return $this->query($sql);
    }

    function delete($table, $criteria)
    {
        $whereParts = array();
        foreach ($criteria as $key => $value) {
            $whereParts[] = '`' . $key . '` = ' . $this->escapeValue($value);
        }

        $sql = 'DELETE FROM `' . $table . '` WHERE ' . implode(' AND ', $whereParts);

        return $this->query($sql);
    }
}
