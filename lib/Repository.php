<?php

class Repository
{
    var $database;

    function Repository(&$database)
    {
        $this->database =& $database;
    }

    function getDatabase()
    {
        return $this->database;
    }

    function fetchOne($sql)
    {
        $result = $this->database->query($sql);

        if (!$result) {
            return false;
        }

        return $this->database->fetchAssoc($result);
    }

    function fetchAll($sql)
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

    function buildInClause($values)
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
