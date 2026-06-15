<?php


namespace Docile\Tests;

use Docile\Docile;
use Docile\Tests\Unit\CoreTrain;

class TrainDocile extends Docile
{
    public static function train()
    {
        $location_commmand = "./vendor/bin/phpunit ./framework/Tests/Unit";
        exec($location_commmand, $output);
        echo implode("\n", $output) . "\n";
    }
}
