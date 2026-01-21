<?php

require_once __DIR__ . '/Database.php';

class Repository
{
    protected Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function fetchOne($sql)
    {
        $result = $this->database->query($sql);

        if (!$result) {
            return false;
        }

        return $this->database->fetchAssoc($result);
    }

    public function fetchAll($sql)
    {
        $result = $this->database->query($sql);

        if (!$result) {
            return array();
        }

        $rows = array();

        while ($row = $this->database->fetchAssoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function buildInClause($values)
    {
        $escaped = array();

        foreach ($values as $value) {
            $escaped[] = "'" . $this->database->escapeString($value) . "'";
        }

        if (0 === count($escaped)) {
            return '';
        }

        return implode(', ', $escaped);
    }
}
