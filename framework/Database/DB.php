<?php

namespace Docile\Database;

use Docile\Docile;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class DB extends Docile
{
    public static function connect()
    {
        try {
            $capsule = new Capsule;

            $capsule->addConnection([
                'driver'    => env('DB_CONNECTION'),
                'host'      => env('DB_HOST'),
                'port'      => env('DB_PORT'),
                'database'  => env('DB_DATABASE'),
                'username'  => env('DB_USERNAME'),
                'password'  => env('DB_PASSWORD'),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ]);

            $capsule->setAsGlobal();

            $capsule->bootEloquent();
        } catch (\Throwable $th) {
            if (env("APP_DEBUG")) {
                return core_view("server-error", ['title' => "Database connection failed!", 'message' => $th->getMessage()]);
            } else {
                throw new Exception("Database connection failed!");
            }
        }
    }
}
