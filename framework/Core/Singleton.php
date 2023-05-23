<?php

namespace Docile\Core;

class Singleton
{
    private static $instances = [];

    private function __construct()
    {
        // Initialize the framework here
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

    public function run()
    {
        // Logic to start the framework
        echo "Framework is running!";
    }
}
