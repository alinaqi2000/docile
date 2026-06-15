<?php
error_reporting(1);

// Database configuration
define('DB_CONNECTION', env("DB_CONNECTION") ?? 'mysql');
define('DB_HOST', env("DB_HOST") ?? 'localhost');
define('DB_PORT', env("DB_PORT") ?? '3306');
define('DB_DATABASE', env("DB_DATABASE") ?? 'docile_db');
define('DB_USERNAME', env("DB_USERNAME") ?? 'root');
define('DB_PASSWORD', env("DB_PASSWORD") ?? '');

if (DB_CONNECTION == 'sqlite') {
    define('DB_PATH', __DIR__ . "/../database/" . DB_DATABASE);
}
