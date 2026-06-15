<?php

namespace Docile\Console\Actions;

use Database\Migrations\DatabaseMigration;
use Docile\Database\DB;
use Exception;

class MigrateAction
{
    public static function perform($args)
    {
        try {
            DB::connect(TRUE);

            if ($args[2] == '--refresh') {
                DatabaseMigration::clear();
                DatabaseMigration::drop();
            }

            DatabaseMigration::run();
            console_success(MIGRATION_SUCCESSFUL);
        } catch (\PDOException $pd) {
            if (env("APP_DEBUG")) {
                console_error($pd->getMessage());
            } else {
                throw new Exception("Database failure!");
            }
        }
    }
}
