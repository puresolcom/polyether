<?php
function sanitizeKey($key)
{
    $rawKey = $key;
    $key = strtolower($key);
    $key = preg_replace('/[^a-z0-9_\-]/', '', $key);

    return Plugin::apply_filters('sanitize_key', $key, $rawKey);
}