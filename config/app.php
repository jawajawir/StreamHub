<?php
return [
    'name'      => env('APP_NAME', 'StreamHub'),
    'env'       => env('APP_ENV', 'production'),
    'debug'     => env('APP_DEBUG', 'false') === 'true',
    'url'       => rtrim((string) env('APP_URL', ''), '/'),
    'timezone'  => env('APP_TIMEZONE', 'UTC'),
    'key'       => env('APP_KEY', ''),
    'admin_path'=> trim((string) env('ADMIN_PATH', 'admin'), '/'),
    'demo_seed' => env('DEMO_SEED', 'false') === 'true',
    'cron_token'=> env('CRON_TOKEN', ''),
];
