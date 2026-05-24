<?php
return [
    'driver'     => env('MAIL_DRIVER', 'smtp'),
    'host'       => env('MAIL_HOST', ''),
    'port'       => (int) env('MAIL_PORT', 587),
    'user'       => env('MAIL_USER', ''),
    'pass'       => env('MAIL_PASS', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from_address' => env('MAIL_FROM_ADDRESS', ''),
    'from_name'    => env('MAIL_FROM_NAME', 'StreamHub'),
];
