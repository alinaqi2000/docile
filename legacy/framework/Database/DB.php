<?php

namespace Docile\Database;

use Docile\Docile;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;
use PDOException;

class DB extends Docile
{
    public static function connect($terminal = FALSE)
    {

        try {
            $capsule = new Capsule;
            if (DB_CONNECTION == 'sqlite') {

                $capsule->addConnection([
                    'driver'    => DB_CONNECTION,
                    'host'      => DB_HOST,
                    'port'      => DB_PORT,
                    'database'  => DB_PATH,
                    'username'  => DB_USERNAME,
                    'password'  => DB_PASSWORD,
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ]);
            } else {

                $capsule->addConnection([
                    'driver'    => DB_CONNECTION,
                    'host'      => DB_HOST,
                    'port'      => DB_PORT,
                    'database'  => DB_DATABASE,
                    'username'  => DB_USERNAME,
                    'password'  => DB_PASSWORD,
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ]);
            }
            $capsule->setAsGlobal();

            $capsule->bootEloquent();
        } catch (\Throwable $th) {
            if (env("APP_DEBUG")) {
                if ($terminal)
                    console_error(DATABASE_NOT_FOUND);
                else
                    return core_view("server-error", ['title' => "Database connection failed!", 'message' => $th->getMessage()]);
            } else {
                throw new Exception("Database connection failed!");
            }
        } catch (PDOException $pd) {
            if (env("APP_DEBUG")) {
                if ($terminal)
                    console_error($pd->getMessage());
                else
                    return core_view("server-error", ['title' => "Database connection failed!", 'message' => $pd->getMessage()]);
            } else {
                throw new Exception("Database connection failed!");
            }
        }


    }
}
