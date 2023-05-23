<?php


namespace Docile\Console\Commands;

class RunServerCommand
{
    public function execute()
    {
        $host = 'localhost';
        $port = 8000;

        $command = 'php -S ' . $host . ':' . $port . ' -t public';

        echo "Server running on http://{$host}:{$port}\n";

        exec($command);
    }
}
