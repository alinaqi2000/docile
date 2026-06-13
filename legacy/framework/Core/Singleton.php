<?php

namespace Docile\Core;

class Singleton
{
    private static $instances = [];
    private function __construct()
    {
    }
    private function __clone()
    {
    }
    public function __sleep()
    {
    }
    public function __wakeup()
    {
    }

    public static function getInstance()
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    // Other methods and properties of the framework


}
