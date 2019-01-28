<?php

class CONFIG
{
    public static $CONFIG = [
        'ATSAST_CDN' => 'https://static.1cf.co',

        'PRO_MYSQL_HOST' => 'localhost',
        'PRO_MYSQL_PORT' => '3306',
        'PRO_MYSQL_USER' => 'root',
        'PRO_MYSQL_DB' => 'sast_finance',
        'PRO_MYSQL_PASS' => 'codeofwinter',
        'PRO_MYSQL_CHARSET' => 'utf8',

        'DEBUG_MYSQL_HOST' => 'localhost',
        'DEBUG_MYSQL_PORT' => '3306',
        'DEBUG_MYSQL_USER' => 'root',
        'DEBUG_MYSQL_DB' => 'sast_finance',
        'DEBUG_MYSQL_PASS' => 'root',
        'DEBUG_MYSQL_CHARSET' => 'utf8',
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
