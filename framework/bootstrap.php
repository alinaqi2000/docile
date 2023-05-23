<?php

use Dotenv\Dotenv;

require_once  __DIR__ . '/../config/routes.php';

$dotenv =  Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create an instance of the docile

use Docile\Docile;

Docile::launch();
