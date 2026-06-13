<?php


// ANSI escape codes for text colors
const ANSI_RESET = "\033[0m";
const ANSI_RED = "\033[31m";
const ANSI_GREEN = "\033[32m";
const ANSI_YELLOW = "\033[33m";
const ANSI_BLUE = "\033[34m";

if (!function_exists("dd")) {

    function dd($variable)
    {
        echo '<pre>';
        var_dump($variable);
        echo '</pre>';
        die();
    }
}

if (!function_exists("console_error")) {

    function console_error($message_code)
    {
        echo ANSI_RED . "Error! " . $message_code . ANSI_RESET . "\n";
        die();
    }
}
if (!function_exists("console_success")) {

    function console_success($message_code)
    {
        echo ANSI_GREEN . "Success! " . $message_code . ANSI_RESET . "\n";
        die();
    }
}
