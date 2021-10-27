<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_Validator
{
    public static function offerwhere_is_valid_uuid($val): bool
    {
        return $val !== null &&
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $val);
    }

    public static function offerwhere_is_valid_jwt($val): bool
    {
        return $val !== null && preg_match('/^[^\s]+\.[^\s]+\.[^\s]+$/', $val);
    }

    public static function offerwhere_is_valid_user_number($val): bool
    {
        return $val !== null && preg_match('/^([\w\d]{8})$/', $val);
    }
}
