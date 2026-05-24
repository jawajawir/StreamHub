<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Core\App;
use App\Core\Router;
use App\Core\Request;

// If app is not installed, force the installer.
$installed = is_file(STREAMHUB_BASE . '/storage/install.lock');
$envExists = is_file(STREAMHUB_BASE . '/.env');

$request = Request::fromGlobals();
$path    = $request->path();

if (!$installed || !$envExists) {
    if (strpos($path, '/install') !== 0) {
        header('Location: /install', true, 302);
        exit;
    }
    require STREAMHUB_BASE . '/app/Installer/installer.php';
    exit;
}

if (strpos($path, '/install') === 0) {
    // Installer is locked - show a friendly notice.
    http_response_code(403);
    echo '<!DOCTYPE html><title>Installer locked</title><meta name="robots" content="noindex">';
    echo '<style>body{font:16px system-ui;max-width:560px;margin:80px auto;padding:0 20px;color:#222}</style>';
    echo '<h1>Installer is locked</h1><p>This site is already installed. Remove <code>storage/install.lock</code> manually if you need to reinstall.</p>';
    exit;
}

$router = new Router();
require STREAMHUB_BASE . '/routes/web.php';
require STREAMHUB_BASE . '/routes/admin.php';
require STREAMHUB_BASE . '/routes/api.php';

App::boot();
$router->dispatch($request);
