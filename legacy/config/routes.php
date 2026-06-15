<?php

use Database\Migrations\DatabaseMigration;
use Docile\Core\Router;

Router::get('/', 'HomeController@index');

Router::get('/protected', 'HomeController@personal')->middlewares(['auth'], ['/']);

Router::get('/up', function () {
    DatabaseMigration::run();
    dd("migrated!");
});
