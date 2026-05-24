<?php
/** @var \App\Core\Router $router */

// Frontend routes - protected by Maintenance + AgeGate middlewares globally.
$router->group(['middleware' => [
    \App\Middleware\MaintenanceMiddleware::class,
    \App\Middleware\AgeGateMiddleware::class,
]], function (\App\Core\Router $router) {
    $router->get ('/',                    'App\Controllers\Frontend\HomeController@index');

    // Watch
    $router->get ('/watch/{slug}',        'App\Controllers\Frontend\WatchController@show');
    $router->post('/watch/{id}/like',     'App\Controllers\Frontend\AccountController@likeContent');
    $router->post('/watch/{id}/dislike', 'App\Controllers\Frontend\AccountController@dislikeContent');
    $router->post('/watch/{id}/favorite','App\Controllers\Frontend\AccountController@toggleFavorite');
    $router->post('/watch/{id}/report',  'App\Controllers\Frontend\WatchController@reportBroken');
    $router->get ('/playback/{token}',   'App\Controllers\Frontend\WatchController@playback');

    // Discovery
    $router->get ('/category/{slug}',  'App\Controllers\Frontend\CategoryController@show');
    $router->get ('/tag/{slug}',       'App\Controllers\Frontend\TagController@show');
    $router->get ('/performer/{slug}', 'App\Controllers\Frontend\PerformerController@show');
    $router->get ('/studio/{slug}',    'App\Controllers\Frontend\StudioController@show');
    $router->get ('/series/{slug}',    'App\Controllers\Frontend\SeriesController@show');
    $router->get ('/search',           'App\Controllers\Frontend\SearchController@index');
    $router->get ('/latest',           'App\Controllers\Frontend\ListingController@latest');
    $router->get ('/trending',         'App\Controllers\Frontend\ListingController@trending');
    $router->get ('/most-viewed',      'App\Controllers\Frontend\ListingController@mostViewed');

    // Pages
    $router->get ('/page/{slug}',      'App\Controllers\Frontend\PageController@show');

    // Auth
    $router->get ('/login',            'App\Controllers\Frontend\AuthController@showLogin');
    $router->post('/login',            'App\Controllers\Frontend\AuthController@login');
    $router->get ('/register',         'App\Controllers\Frontend\AuthController@showRegister');
    $router->post('/register',         'App\Controllers\Frontend\AuthController@register');
    $router->post('/logout',           'App\Controllers\Frontend\AuthController@logout');
    $router->get ('/forgot-password',  'App\Controllers\Frontend\AuthController@showForgotPassword');
    $router->post('/forgot-password',  'App\Controllers\Frontend\AuthController@sendPasswordReset');
    $router->get ('/reset-password/{token}', 'App\Controllers\Frontend\AuthController@showResetPassword');
    $router->post('/reset-password',   'App\Controllers\Frontend\AuthController@resetPassword');
    $router->get ('/verify-email/{token}', 'App\Controllers\Frontend\AuthController@verifyEmail');

    // Membership
    $router->get ('/membership',         'App\Controllers\Frontend\MembershipController@index');
    $router->post('/membership/request', 'App\Controllers\Frontend\MembershipController@requestUpgrade');

    // Account (auth-required actions enforce themselves via UserAuthMiddleware in controller)
    $router->get ('/account',                'App\Controllers\Frontend\AccountController@dashboard');
    $router->get ('/account/history',        'App\Controllers\Frontend\AccountController@history');
    $router->post('/account/history/clear',  'App\Controllers\Frontend\AccountController@clearHistory');
    $router->post('/account/history/pause',  'App\Controllers\Frontend\AccountController@pauseHistory');
    $router->get ('/account/favorites',      'App\Controllers\Frontend\AccountController@favorites');
    $router->post('/account/privacy',        'App\Controllers\Frontend\AccountController@updatePrivacy');
    $router->post('/account/logout-devices', 'App\Controllers\Frontend\AccountController@logoutAllDevices');

    // System pages
    $router->get ('/maintenance', 'App\Controllers\Frontend\SystemPageController@maintenance');
    $router->get ('/403',         'App\Controllers\Frontend\SystemPageController@forbidden');
    $router->get ('/404',         'App\Controllers\Frontend\SystemPageController@notFound');
});

// Age gate (outside age gate middleware so it can be displayed)
$router->group(['middleware' => [\App\Middleware\MaintenanceMiddleware::class]], function (\App\Core\Router $router) {
    $router->get ('/age-gate',         'App\Controllers\Frontend\AgeGateController@show');
    $router->post('/age-gate/accept',  'App\Controllers\Frontend\AgeGateController@accept');
    $router->get ('/sitemap.xml',      'App\Controllers\Frontend\SitemapController@index');
    $router->get ('/robots.txt',       'App\Controllers\Frontend\SeoController@robots');
});
