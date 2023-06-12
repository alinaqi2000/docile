<?php

namespace Database\Migrations;

use Database\Repositories\UserRepository;
use Docile\Database\DBMigration;
use Docile\Database\Migration;

class DatabaseMigration extends Migration
{

    public static function run()
    {
        static::clear();

        UserRepository::up();;
    }
}
