<?php
/** @var \App\Core\Router $router */

$adminPath = trim((string) config('app.admin_path', 'admin'), '/');

// Login routes (no AdminAuth)
$router->group(['prefix' => $adminPath, 'middleware' => [
    \App\Middleware\MaintenanceMiddleware::class,
    \App\Middleware\AdminGuestMiddleware::class,
]], function (\App\Core\Router $router) {
    $router->get ('/login', 'App\Controllers\Admin\AuthController@showLogin');
    $router->post('/login', 'App\Controllers\Admin\AuthController@login');
});

// Authenticated admin routes
$router->group(['prefix' => $adminPath, 'middleware' => [
    \App\Middleware\MaintenanceMiddleware::class,
    \App\Middleware\AdminAuthMiddleware::class,
]], function (\App\Core\Router $router) {
    $router->post('/logout', 'App\Controllers\Admin\AuthController@logout');

    $router->get ('/',                'App\Controllers\Admin\DashboardController@index');

    // User Manager
    $router->get ('/users',                    'App\Controllers\Admin\UserManagerController@index');
    $router->get ('/users/{id}',               'App\Controllers\Admin\UserManagerController@show');
    $router->post('/users/{id}/membership',    'App\Controllers\Admin\UserManagerController@updateMembership');
    $router->post('/users/{id}/status',        'App\Controllers\Admin\UserManagerController@updateStatus');
    $router->post('/users/{id}/ban',           'App\Controllers\Admin\UserManagerController@ban');

    // Membership requests inbox (manual payment workflow)
    $router->get ('/membership-requests',                'App\Controllers\Admin\MembershipRequestController@index');
    $router->get ('/membership-requests/{id}',           'App\Controllers\Admin\MembershipRequestController@show');
    $router->post('/membership-requests/{id}/approve',   'App\Controllers\Admin\MembershipRequestController@approve');
    $router->post('/membership-requests/{id}/reject',    'App\Controllers\Admin\MembershipRequestController@reject');

    // Video Manager
    $router->get ('/videos',              'App\Controllers\Admin\VideoManagerController@index');
    $router->get ('/videos/create',       'App\Controllers\Admin\VideoManagerController@create');
    $router->post('/videos',              'App\Controllers\Admin\VideoManagerController@store');
    $router->get ('/videos/{id}/edit',    'App\Controllers\Admin\VideoManagerController@edit');
    $router->post('/videos/{id}',         'App\Controllers\Admin\VideoManagerController@update');
    $router->post('/videos/{id}/status',  'App\Controllers\Admin\VideoManagerController@updateStatus');
    $router->post('/videos/{id}/delete',  'App\Controllers\Admin\VideoManagerController@deleteOrArchive');

    // Taxonomy
    $router->get ('/categories',                'App\Controllers\Admin\TaxonomyController@categories');
    $router->post('/categories',                'App\Controllers\Admin\TaxonomyController@storeCategory');
    $router->post('/categories/{id}',           'App\Controllers\Admin\TaxonomyController@updateCategory');
    $router->post('/categories/{id}/delete',    'App\Controllers\Admin\TaxonomyController@deleteCategory');
    $router->get ('/tags',                      'App\Controllers\Admin\TaxonomyController@tags');
    $router->post('/tags',                      'App\Controllers\Admin\TaxonomyController@storeTag');
    $router->post('/tags/{id}',                 'App\Controllers\Admin\TaxonomyController@updateTag');
    $router->post('/tags/{id}/delete',          'App\Controllers\Admin\TaxonomyController@deleteTag');
    $router->get ('/performers',                'App\Controllers\Admin\TaxonomyController@performers');
    $router->post('/performers',                'App\Controllers\Admin\TaxonomyController@storePerformer');
    $router->post('/performers/{id}',           'App\Controllers\Admin\TaxonomyController@updatePerformer');
    $router->post('/performers/{id}/delete',    'App\Controllers\Admin\TaxonomyController@deletePerformer');
    $router->get ('/studios',                   'App\Controllers\Admin\TaxonomyController@studios');
    $router->post('/studios',                   'App\Controllers\Admin\TaxonomyController@storeStudio');
    $router->post('/studios/{id}',              'App\Controllers\Admin\TaxonomyController@updateStudio');
    $router->post('/studios/{id}/delete',       'App\Controllers\Admin\TaxonomyController@deleteStudio');
    $router->get ('/series',                    'App\Controllers\Admin\TaxonomyController@series');
    $router->post('/series',                    'App\Controllers\Admin\TaxonomyController@storeSeries');
    $router->post('/series/{id}',               'App\Controllers\Admin\TaxonomyController@updateSeries');
    $router->post('/series/{id}/delete',        'App\Controllers\Admin\TaxonomyController@deleteSeries');

    // API Manager (Doodstream)
    $router->get ('/api/doodstream',           'App\Controllers\Admin\ApiManagerController@doodstream');
    $router->post('/api/doodstream/key',       'App\Controllers\Admin\ApiManagerController@saveDoodstreamKey');
    $router->post('/api/doodstream/test',      'App\Controllers\Admin\ApiManagerController@testDoodstream');

    // Pages Manager
    $router->get ('/pages',              'App\Controllers\Admin\PagesManagerController@index');
    $router->get ('/pages/{id}/edit',    'App\Controllers\Admin\PagesManagerController@edit');
    $router->post('/pages/{id}',         'App\Controllers\Admin\PagesManagerController@update');
    $router->post('/pages/{id}/publish', 'App\Controllers\Admin\PagesManagerController@publish');

    // Settings Manager
    $router->get ('/settings',            'App\Controllers\Admin\SettingsManagerController@index');
    $router->post('/settings',            'App\Controllers\Admin\SettingsManagerController@update');
    $router->post('/settings/features',   'App\Controllers\Admin\SettingsManagerController@updateFeatures');

    // System
    $router->get ('/system/audit-logs',     'App\Controllers\Admin\SystemController@auditLogs');
    $router->get ('/system/security-events','App\Controllers\Admin\SystemController@securityEvents');
});
