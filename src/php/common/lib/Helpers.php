<?php

namespace App\Lib;

class Helpers
{
    public static function isUUID($str)
    {
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}$/', $str);
    }
}