<?php
return [
    'encryption_key'   => env('ENCRYPTION_KEY', ''),
    'session_name'     => env('SESSION_NAME', 'streamhub_session'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
    'session_secure'   => env('SESSION_SECURE', 'false') === 'true',
    'session_samesite' => env('SESSION_SAMESITE', 'Lax'),

    // Login rate limiting (window seconds, max attempts before lockout)
    'login_window'     => 900,
    'login_max'        => 5,
    'admin_login_max'  => 4,

    // Trusted iframe/embed domains (admin can extend via settings table)
    'iframe_allowlist_default' => [
        'doodstream.com',
        'dood.la',
        'dood.li',
        'dood.so',
        'dood.ws',
        'dood.cx',
        'dood.sh',
        'dood.pm',
        'dood.re',
        'dood.to',
        'dood.watch',
        'dood.work',
        'dood.yt',
        'doodstream.co',
        'd0000d.com',
        'd000d.com',
        'd00d.com',
        'ds2play.com',
        'ds2video.com',
    ],

    // Signed playback URL TTL (seconds)
    'signed_url_ttl'   => 3600,
];
