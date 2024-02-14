<?php

namespace Docile\Console\Actions;

class RunServerAction
{

    public static function perform(...$args)
    {
        $host = 'localhost';
        $port = 8000;

        $command = 'php -S ' . $host . ':' . $port . ' -t public';

        echo "Server running on http://{$host}:{$port}\n";

        exec($command);
    }
}
