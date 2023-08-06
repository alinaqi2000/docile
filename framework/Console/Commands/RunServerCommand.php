<?php


namespace Docile\Console\Commands;

use Docile\Tests\TrainDocile;

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
    public function test()
    {
        echo "Training Docile... \n\n";
        TrainDocile::train();
        echo "\nDocile Trained! \n\n";
    }
}
