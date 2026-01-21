<?php

class Database
{
    private $host;
    private $user;
    private $password;
    private $dbName;

    /**
     * @var \mysqli|null
     */
    private $link;

    public function __construct($host, $user, $password, $dbName)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->link = null;
    }

    public function connect()
    {
        if ($this->link instanceof \mysqli) {
            return $this->link;
        }

        $link = @mysqli_connect($this->host, $this->user, $this->password, $this->dbName);

        if (!$link) {
            return false;
        }

        mysqli_set_charset($link, 'utf8mb4');

        $this->link = $link;

        return $this->link;
    }

    public function ensureConnection()
    {
        if (!$this->link) {
            $this->connect();
        }

        return $this->link;
    }

    public function escapeString($value)
    {
        $link = $this->ensureConnection();

        if (!$link) {
            return '';
        }

        return mysqli_real_escape_string($link, (string) $value);
    }

    public function escapeValue($value)
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

    public function query($sql)
    {
        $link = $this->ensureConnection();

        if (!$link) {
            return false;
        }

        return mysqli_query($link, $sql);
    }

    public function fetchAssoc($result)
    {
        if (!$result) {
            return false;
        }

        return mysqli_fetch_assoc($result);
    }

    public function insert($table, $data)
    {
        $keys = array_keys($data);
        $escapedValues = array();
        foreach ($data as $value) {
            $escapedValues[] = $this->escapeValue($value);
        }

        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $keys) . '`) VALUES (' . implode(',', $escapedValues) . ')';

        return $this->query($sql);
    }

    public function getInsertId()
    {
        $link = $this->ensureConnection();

        if (!$link) {
            return 0;
        }

        return mysqli_insert_id($link);
    }

    public function update($table, $data, $criteria)
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

    public function delete($table, $criteria)
    {
        $whereParts = array();
        foreach ($criteria as $key => $value) {
            $whereParts[] = '`' . $key . '` = ' . $this->escapeValue($value);
        }

        $sql = 'DELETE FROM `' . $table . '` WHERE ' . implode(' AND ', $whereParts);

        return $this->query($sql);
    }
}
