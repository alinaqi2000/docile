<?php

namespace Docile\Database;

use Docile\Docile;


class Migration extends Docile
{
    public static function clear()
    {

        if (file_exists(DB_PATH)) {
            unlink(DB_PATH);
        }

        if (!file_exists(DB_PATH)) {
            fopen(DB_PATH, "w");
        }
    }
}
