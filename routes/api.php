<?php
/** @var \App\Core\Router $router */

// Cron entrypoint - token-protected, no UI.
$router->any('/cron/run', 'App\Controllers\Frontend\CronController@run');
