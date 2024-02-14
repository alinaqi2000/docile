<?php

namespace Database\Migrations;

use Database\Repositories\UserRepository;
use Docile\Database\DBMigration;
use Docile\Database\Migration;

class DatabaseMigration extends Migration
{

    private static $repositories = [
        UserRepository::class
    ];
    public static function run()
    {
        static::ups(static::$repositories);
    }
    public static function drop()
    {
        static::downs(static::$repositories);
    }
}
