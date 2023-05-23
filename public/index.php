<?php

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv =  Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once  __DIR__ . '/../config/constants.php';

session_start();

require_once  __DIR__ . '/../framework/bootstrap.php';
