<?php

class CONFIG
{
    public static $CONFIG = [
        'ATSAST_CDN' => 'https://static.1cf.co',
    ];

    public static function GET($config, $default = null)
    {
        if (array_key_exists($config, self::$CONFIG)) {
            return self::$CONFIG[$config];
        } else {
            return $default;
        }
    }
}
