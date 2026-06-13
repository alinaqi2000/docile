<?php


namespace Docile\Console\Commands;

use Docile\Console\Actions\MigrateAction;
use Docile\Console\Actions\RunServerAction;
use Docile\Tests\TrainDocile;

class RunCommand
{
    public function server(...$args)
    {
        RunServerAction::perform($args);
    }
    public function migrate(...$args)
    {
        MigrateAction::perform($args);
    }
    public function test()
    {
        echo "Training Docile... \n\n";
        TrainDocile::train();
        echo "\nDocile Trained! \n\n";
    }
}
