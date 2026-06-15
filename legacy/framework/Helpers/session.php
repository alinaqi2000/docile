<?php


if (!function_exists("session")) {
    function session()
    {
        return Session::class;
    }
}
