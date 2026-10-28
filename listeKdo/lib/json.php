<?php
/*
 * Encodeur JSON minimal : PHP 4 n'a pas json_encode.
 * Les chaînes doivent être en UTF-8 (elles sont transmises telles quelles).
 */

function kdo_json($value)
{
    if (true === $value) {
        return 'true';
    }

    if (false === $value) {
        return 'false';
    }

    if (is_null($value)) {
        return 'null';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_array($value)) {
        if (kdo_json_is_list($value)) {
            $items = array();
            foreach ($value as $item) {
                $items[] = kdo_json($item);
            }

            return '[' . implode(',', $items) . ']';
        }

        $pairs = array();
        foreach ($value as $key => $item) {
            $pairs[] = kdo_json_string((string) $key) . ':' . kdo_json($item);
        }

        return '{' . implode(',', $pairs) . '}';
    }

    return kdo_json_string((string) $value);
}

function kdo_json_is_list($array)
{
    $expected = 0;
    foreach ($array as $key => $value) {
        if ($key !== $expected) {
            return false;
        }
        $expected++;
    }

    return true;
}

function kdo_json_string($string)
{
    $string = str_replace(
        array('\\', '"', '/', "\n", "\r", "\t", "\x08", "\x0c"),
        array('\\\\', '\\"', '\\/', '\\n', '\\r', '\\t', '\\b', '\\f'),
        $string
    );

    // Autres caractères de contrôle (callback nommé : le modificateur /e n'existe plus en PHP 7+).
    $string = preg_replace_callback('/[\x00-\x1f]/', 'kdo_json_control_char', $string);

    return '"' . $string . '"';
}

function kdo_json_control_char($matches)
{
    return sprintf('\\u%04x', ord($matches[0]));
}
