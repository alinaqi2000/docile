<?php

namespace Docile\Database;

use Docile\Docile;
use Illuminate\Database\Capsule\Manager as Capsule;

class DBRepository extends Docile
{
    protected static function drop($table)
    {
        Capsule::schema()->dropIfExists($table);
    }
}
