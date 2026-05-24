<?php
return [
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => (int) env('DB_PORT', 3306),
    'name'    => env('DB_NAME', 'streamhub'),
    'user'    => env('DB_USER', 'root'),
    'pass'    => env('DB_PASS', ''),
    'prefix'  => env('DB_PREFIX', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
];
