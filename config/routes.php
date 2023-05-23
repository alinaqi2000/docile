<?php

use Docile\Core\Router;

Router::get('/', 'HomeController@index');
Router::get('/users', 'HomeController@users');
Router::get('/users/{user}', 'HomeController@show');
Router::get('/users/{user}/car/{car}', 'HomeController@show');
