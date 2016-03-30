<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

function filterObjectList($list, $args = array(), $operator = 'and', $field = false)
{
    if ( ! is_array($list)) {
        return array();
    }

    $list = listFilter($list, $args, $operator);

    if ($field) {
        $list = array_pluck($list, $field);
    }

    return $list;
}

function listFilter($list, $args = array(), $operator = 'AND')
{
    if ( ! is_array($list)) {
        return array();
    }

    if (empty($args)) {
        return $list;
    }

    $operator = strtoupper($operator);
    $count = count($args);
    $filtered = array();

    foreach ($list as $key => $obj) {
        $to_match = (array)$obj;

        $matched = 0;
        foreach ($args as $m_key => $m_value) {
            if (array_key_exists($m_key, $to_match) && $m_value == $to_match[ $m_key ]) {
                $matched++;
            }
        }

        if (('AND' == $operator && $matched == $count) || ('OR' == $operator && $matched > 0) || ('NOT' == $operator && 0 == $matched)) {
            $filtered[ $key ] = $obj;
        }
    }

    return $filtered;
}

function degrade($entity)
{
    if (is_array($entity)) {
        return $entity;
    }

    if ($entity instanceof Model) {
        return $entity->toArray();
    }

    if ($entity instanceof Collection) {
        return $entity->toArray();
    }

    return (array)$entity;

}

function isJSON($string)
{
    return is_string($string) && is_array(json_decode($string,
        true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}

function jsonizeMaybe($value)
{
    if (is_array($value) || is_object($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    return $value;
}

function unjsonizeMaybe($original)
{
    if (isJSON($original)) {
        return json_decode($original);
    }

    return $original;
}

function se($value)
{

    if (is_string($value)) {
        return e($value);
    }

    return $value;
}