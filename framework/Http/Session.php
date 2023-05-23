<?php

namespace Docile\Http;

use Docile\Docile;

class Session extends Docile
{

    public static function put($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {

        if (static::hasFlash($key)) {
            $value = $_SESSION['_flash'][$key];
            static::forgetFlash($key);
            return $value ?? $default;
        }

        return $_SESSION[$key] ?? $default;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function forget($key)
    {
        unset($_SESSION[$key]);
    }


    public static function flash($key, $value)
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function hasFlash($key)
    {
        return isset($_SESSION['_flash'][$key]);
    }

    private static function forgetFlash($key)
    {
        unset($_SESSION['_flash'][$key]);
    }

    public function flush()
    {
        session_unset();
        session_destroy();
    }
}
