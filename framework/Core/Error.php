<?php

namespace Docile\Core;

use Docile\Docile;

class Error extends Docile
{
    private string $type;

    public static function destroy(string $message = "", string $type = "",)
    {
        $that = static::getInstance();
        $that->type = $type;
        switch ($that->type) {
            case 'middleware':
                core_view("server-error", ["title" => "Unauthorized!", "message" => $message]);
                break;
            default:
                core_view("server-error", ["title" => "Error!", "message" => "Something went wrong..."]);
                break;
        }
    }
}
