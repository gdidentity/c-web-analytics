<?php

namespace CWebAnalytics;

class Settings
{
    public static $optionName = 'cwa';

    public static function get()
    {
        return get_option(self::$optionName);
    }
}
