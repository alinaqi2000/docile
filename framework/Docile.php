<?php

namespace Docile;

use Docile\Database\DB;
use Docile\Core\Router;
use Docile\Core\Singleton;

class Docile extends Singleton
{
    public static function launch()
    {
        DB::connect();
        Router::dispatch();
    }
}
