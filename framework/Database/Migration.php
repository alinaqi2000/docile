<?php

namespace Docile\Database;

use Docile\Docile;


class Migration extends Docile
{

    public static function ups($repositories)
    {
        foreach ($repositories as $repository) {
            $repository::up();
        }
    }
    public static function downs($repositories)
    {
        foreach ($repositories as $repository) {
            $repository::down();
        }
    }
    public static function clear($refresh = FALSE)
    {
        if (defined('DB_PATH')) {
            if ($refresh) {
                if (file_exists(DB_PATH)) {
                    unlink(DB_PATH);
                }
            }
            if (!file_exists(DB_PATH)) {
                fopen(DB_PATH, "w");
            }
        }
    }
}
