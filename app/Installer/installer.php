<?php
declare(strict_types=1);

/**
 * StreamHub Installer
 * - Self-contained (does not require .env or DB to exist).
 * - Premium UI with Tailwind CDN (allowed for installer phase only).
 * - Steps: welcome → requirements → database → app → migrate → admin → cron → done.
 */

namespace App\Installer;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Encryption;
use App\Core\Env;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Session;
use App\Services\RateLimitService;
use App\Services\SecurityService;

Session::instance();

$lockFile = STREAMHUB_BASE . '/storage/install.lock';
$envFile  = STREAMHUB_BASE . '/.env';

if (is_file($lockFile)) {
    http_response_code(403);
    echo render('locked', []);
    exit;
}

$request = Request::fromGlobals();
$action  = $request->query('step', 'welcome');
$flash   = Session::instance()->pull('_install_flash', []);

// Rate limit installer page hits per IP
$ipHash = (string) hash_ip($request->ip());
if (!RateLimitService::hit('installer', $ipHash, 60, 60)) {
    http_response_code(429);
    echo '<h1>Too many requests</h1><p>Please slow down and try again shortly.</p>';
    exit;
}

if ($request->isPost()) {
    if (!Csrf::check((string) $request->post('_csrf'))) {
        SecurityService::logEvent('csrf_failed', 'warning', $request, ['where' => 'installer']);
        Session::instance()->set('_install_flash', ['type' => 'error', 'message' => 'CSRF token mismatch. Please retry.']);
        header('Location: /install?step=' . urlencode($action));
        exit;
    }
    handlePost($action, $request, $envFile, $lockFile);
    exit;
}

echo render($action, ['flash' => $flash]);
exit;

// ----------------------------------------------------------------------------

function handlePost(string $step, Request $request, string $envFile, string $lockFile): void
{
    $session = Session::instance();
    $state = $session->get('_install_state', []);

    switch ($step) {
        case 'database':
            $cfg = [
                'host'    => trim((string) $request->post('db_host', '127.0.0.1')),
                'port'    => (int) $request->post('db_port', 3306),
                'name'    => trim((string) $request->post('db_name', '')),
                'user'    => trim((string) $request->post('db_user', '')),
                'pass'    => (string) $request->post('db_pass', ''),
                'prefix'  => trim((string) $request->post('db_prefix', '')),
                'charset' => 'utf8mb4',
            ];
            if ($cfg['name'] === '' || $cfg['user'] === '') {
                $session->set('_install_flash', ['type' => 'error', 'message' => 'Database name and user are required.']);
                header('Location: /install?step=database'); return;
            }
            $result = Database::tryConnect($cfg);
            if (!$result['ok']) {
                $session->set('_install_flash', ['type' => 'error', 'message' => 'Connection failed: ' . $result['error']]);
                header('Location: /install?step=database'); return;
            }
            $state['db'] = $cfg;
            $session->set('_install_state', $state);
            $session->set('_install_flash', ['type' => 'success', 'message' => 'Connection successful.']);
            header('Location: /install?step=app'); return;

        case 'app':
            $appName = trim((string) $request->post('app_name', 'StreamHub'));
            $appUrl  = trim((string) $request->post('app_url', ''));
            $tz      = trim((string) $request->post('timezone', 'UTC'));
            $forceHttps = $request->post('force_https') ? 'true' : 'false';
            $adminPath  = trim((string) $request->post('admin_path', 'admin'), '/');
            if ($appName === '' || $appUrl === '') {
                $session->set('_install_flash', ['type' => 'error', 'message' => 'Site name and URL are required.']);
                header('Location: /install?step=app'); return;
            }
            $state['app'] = [
                'name' => $appName, 'url' => rtrim($appUrl, '/'), 'timezone' => $tz,
                'force_https' => $forceHttps === 'true',
                'admin_path' => $adminPath ?: 'admin',
            ];
            $session->set('_install_state', $state);
            // Write .env now so subsequent steps have config.
            writeEnv($envFile, $state);
            // Reload env for current request
            Env::load($envFile);
            header('Location: /install?step=migrate'); return;

        case 'migrate':
            if (!isset($state['db'])) { header('Location: /install?step=database'); return; }
            try {
                Database::setInstance(null);
                $db = Database::getInstance();
                $migrator = new Migrator($db->pdo());
                $mig  = $migrator->migrate();
                $seed = $migrator->seed();
                $state['migration_summary'] = [
                    'applied'      => $mig['applied'],
                    'skipped'      => $mig['skipped'],
                    'seed_applied' => $seed['applied'],
                    'seed_skipped' => $seed['skipped'],
                ];
                $session->set('_install_state', $state);
                $session->set('_install_flash', ['type' => 'success', 'message' => 'Database setup complete.']);
                header('Location: /install?step=admin'); return;
            } catch (\Throwable $e) {
                logInstall('Migration failed: ' . $e->getMessage());
                $session->set('_install_flash', ['type' => 'error', 'message' => 'Migration failed: ' . $e->getMessage()]);
                header('Location: /install?step=migrate'); return;
            }

        case 'admin':
            $username = trim((string) $request->post('username', ''));
            $email    = trim((string) $request->post('email', ''));
            $password = (string) $request->post('password', '');
            $confirm  = (string) $request->post('password_confirmation', '');
            $errors = [];
            if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) $errors['username'] = 'Username 3-80 chars (letters/numbers/_-.).';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required.';
            if (mb_strlen($password) < 10) $errors['password'] = 'Password must be at least 10 characters.';
            elseif ($password !== $confirm) $errors['password'] = 'Password confirmation does not match.';
            if ($errors) {
                $session->set('_install_errors', $errors);
                $session->set('_install_old', ['username' => $username, 'email' => $email]);
                header('Location: /install?step=admin'); return;
            }
            try {
                $db = Database::getInstance();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Idempotent
                $existing = $db->fetch('SELECT id FROM admin_users WHERE username = :u OR email = :e LIMIT 1', [':u' => $username, ':e' => $email]);
                if ($existing) {
                    $db->update('admin_users', ['password_hash' => $hash, 'status' => 'active'], 'id = :id', [':id' => $existing['id']]);
                } else {
                    $db->insert('admin_users', [
                        'username' => $username,
                        'email'    => $email,
                        'password_hash' => $hash,
                        'role'     => 'superadmin',
                        'status'   => 'active',
                    ]);
                }
                $state['admin_created'] = ['username' => $username, 'email' => $email];
                $session->set('_install_state', $state);
                header('Location: /install?step=cron'); return;
            } catch (\Throwable $e) {
                logInstall('Superadmin create failed: ' . $e->getMessage());
                $session->set('_install_flash', ['type' => 'error', 'message' => 'Failed to create admin: ' . $e->getMessage()]);
                header('Location: /install?step=admin'); return;
            }

        case 'cron':
            // Just acknowledge - cron token already in .env
            header('Location: /install?step=verify'); return;

        case 'verify':
            // Final verify + lock
            try {
                $db = Database::getInstance();
                $hasAdmin = (bool) $db->fetchValue('SELECT COUNT(*) FROM admin_users WHERE status = "active"');
                if (!$hasAdmin) throw new \RuntimeException('No active superadmin found.');
                $hasPlans = (bool) $db->fetchValue('SELECT COUNT(*) FROM membership_plans');
                if (!$hasPlans) throw new \RuntimeException('Default seeds missing.');
                file_put_contents($lockFile, json_encode([
                    'installed_at' => date('c'),
                    'version'      => '1.0.0',
                ]));
                @chmod($lockFile, 0640);
                // Mark installed setting
                $db->query('INSERT INTO settings (setting_group, setting_key, setting_value, value_type) VALUES ("app","installed","1","bool")
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
                // Wipe install state
                $session->forget('_install_state');
                $session->set('_install_flash', ['type' => 'success', 'message' => 'Installation complete.']);
                header('Location: /install?step=done'); return;
            } catch (\Throwable $e) {
                $session->set('_install_flash', ['type' => 'error', 'message' => 'Verification failed: ' . $e->getMessage()]);
                header('Location: /install?step=verify'); return;
            }
    }
    header('Location: /install');
}

function writeEnv(string $envFile, array $state): void
{
    $appKey        = base64_encode(random_bytes(32));
    $encryptionKey = base64_encode(random_bytes(32));
    $cronToken     = bin2hex(random_bytes(24));

    // Preserve existing keys if .env already exists (rare but can happen during retry)
    if (is_file($envFile)) {
        $existing = parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [];
        $appKey        = $existing['APP_KEY'] ?? $appKey;
        $encryptionKey = $existing['ENCRYPTION_KEY'] ?? $encryptionKey;
        $cronToken     = $existing['CRON_TOKEN'] ?? $cronToken;
    }

    $values = [
        'APP_NAME'       => $state['app']['name'] ?? 'StreamHub',
        'APP_ENV'        => 'production',
        'APP_DEBUG'      => 'false',
        'APP_URL'        => $state['app']['url'] ?? '',
        'APP_TIMEZONE'   => $state['app']['timezone'] ?? 'UTC',
        'APP_KEY'        => $appKey,
        'ENCRYPTION_KEY' => $encryptionKey,
        'CRON_TOKEN'     => $cronToken,
        'ADMIN_PATH'     => $state['app']['admin_path'] ?? 'admin',

        'DB_HOST'        => $state['db']['host'] ?? '127.0.0.1',
        'DB_PORT'        => (string) ($state['db']['port'] ?? 3306),
        'DB_NAME'        => $state['db']['name'] ?? '',
        'DB_USER'        => $state['db']['user'] ?? '',
        'DB_PASS'        => $state['db']['pass'] ?? '',
        'DB_PREFIX'      => $state['db']['prefix'] ?? '',
        'DB_CHARSET'     => 'utf8mb4',

        'SESSION_NAME'   => 'streamhub_session',
        'SESSION_LIFETIME' => '7200',
        'SESSION_SECURE' => ($state['app']['force_https'] ?? false) ? 'true' : 'false',
        'SESSION_SAMESITE' => 'Lax',

        'MAIL_DRIVER'    => 'smtp',
        'MAIL_HOST'      => '',
        'MAIL_PORT'      => '587',
        'MAIL_USER'      => '',
        'MAIL_PASS'      => '',
        'MAIL_ENCRYPTION'=> 'tls',
        'MAIL_FROM_ADDRESS' => '',
        'MAIL_FROM_NAME' => $state['app']['name'] ?? 'StreamHub',
        'DEMO_SEED'      => 'false',
    ];
    Env::write($envFile, $values);
    @chmod($envFile, 0640);
}

function logInstall(string $msg): void
{
    @file_put_contents(STREAMHUB_BASE . '/storage/logs/install.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

function checkRequirements(): array
{
    $required = [
        ['key' => 'php',       'label' => 'PHP 8.0 or newer',    'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),  'value' => PHP_VERSION,                  'required' => true],
        ['key' => 'pdo',       'label' => 'PDO extension',        'ok' => extension_loaded('pdo'),                       'value' => extension_loaded('pdo') ? 'yes' : 'missing', 'required' => true],
        ['key' => 'pdo_mysql', 'label' => 'PDO MySQL driver',     'ok' => extension_loaded('pdo_mysql'),                 'value' => extension_loaded('pdo_mysql') ? 'yes' : 'missing', 'required' => true],
        ['key' => 'openssl',   'label' => 'OpenSSL',              'ok' => extension_loaded('openssl'),                   'value' => extension_loaded('openssl') ? 'yes' : 'missing', 'required' => true],
        ['key' => 'mbstring',  'label' => 'mbstring',             'ok' => extension_loaded('mbstring'),                  'value' => extension_loaded('mbstring') ? 'yes' : 'missing', 'required' => true],
        ['key' => 'json',      'label' => 'JSON',                 'ok' => function_exists('json_encode'),                'value' => 'yes',                         'required' => true],
        ['key' => 'fileinfo',  'label' => 'fileinfo',             'ok' => extension_loaded('fileinfo'),                  'value' => extension_loaded('fileinfo') ? 'yes' : 'missing', 'required' => true],
        ['key' => 'session',   'label' => 'Session support',      'ok' => function_exists('session_start'),              'value' => 'yes',                         'required' => true],
        ['key' => 'curl',      'label' => 'cURL (Doodstream API)','ok' => function_exists('curl_init'),                  'value' => function_exists('curl_init') ? 'yes' : 'missing', 'required' => true],
    ];
    $folders = [
        'storage/cache', 'storage/logs', 'storage/uploads',
        'storage/imports', 'storage/exports', 'storage/tmp',
    ];
    $foldersChecks = [];
    foreach ($folders as $f) {
        $abs = STREAMHUB_BASE . '/' . $f;
        if (!is_dir($abs)) @mkdir($abs, 0775, true);
        $foldersChecks[] = [
            'key' => 'fs:' . $f,
            'label' => $f . ' writable',
            'ok' => is_dir($abs) && is_writable($abs),
            'value' => is_writable($abs) ? 'writable' : (is_dir($abs) ? 'not writable' : 'missing'),
            'required' => true,
        ];
    }
    $envWritable = !is_file(STREAMHUB_BASE . '/.env')
        ? is_writable(STREAMHUB_BASE)
        : is_writable(STREAMHUB_BASE . '/.env');
    $optional = [
        ['key' => 'env_writable', 'label' => 'Project root writable for .env', 'ok' => $envWritable,
            'value' => $envWritable ? 'yes' : 'no - create .env manually', 'required' => true],
        ['key' => 'https',  'label' => 'HTTPS enabled',  'ok' => Request::fromGlobals()->isHttps(),
            'value' => Request::fromGlobals()->isHttps() ? 'yes' : 'recommended', 'required' => false],
    ];
    return array_merge($required, $foldersChecks, $optional);
}

function render(string $step, array $context): string
{
    $title = match ($step) {
        'welcome'      => 'Welcome',
        'requirements' => 'Server requirements',
        'database'     => 'Database setup',
        'app'          => 'Application setup',
        'migrate'      => 'Run migrations',
        'admin'        => 'Create superadmin',
        'cron'         => 'Cron & queue setup',
        'verify'       => 'Final verification',
        'done'         => 'Installation complete',
        'locked'       => 'Installer locked',
        default        => 'Installer',
    };
    ob_start();
    $flash = $context['flash'] ?? null;
    $errors = Session::instance()->pull('_install_errors', []);
    $old    = Session::instance()->pull('_install_old', []);
    $state  = Session::instance()->get('_install_state', []);
    $token  = Csrf::token();
    $allSteps = ['welcome','requirements','database','app','migrate','admin','cron','verify','done'];
    $idx = array_search($step, $allSteps, true);
    if ($idx === false) $idx = 0;
    ?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · StreamHub Installer</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { darkMode: 'class', theme: { extend: { colors: { brand: { 500: '#ef4444', 600: '#dc2626' } } } } };
</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
.shadow-card { box-shadow: 0 10px 40px -10px rgba(0,0,0,.4), 0 2px 6px rgba(0,0,0,.2); }
[x-cloak]{display:none}
</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
<div class="min-h-screen flex flex-col">
  <header class="border-b border-zinc-800/60">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg">
          <i data-lucide="play" class="w-5 h-5 text-white"></i>
        </div>
        <div>
          <div class="text-sm font-semibold tracking-tight">StreamHub</div>
          <div class="text-xs text-zinc-500">Installer</div>
        </div>
      </div>
      <div class="text-xs text-zinc-500">PHP <?= e(PHP_VERSION) ?> · MySQL streaming CMS</div>
    </div>
  </header>

  <main class="flex-1 max-w-5xl mx-auto px-6 py-10 w-full">
    <?php // Stepper ?>
    <div class="mb-8">
      <ol class="flex flex-wrap items-center gap-2 text-xs">
        <?php foreach (['welcome'=>'Welcome','requirements'=>'Requirements','database'=>'Database','app'=>'App','migrate'=>'Migrate','admin'=>'Admin','cron'=>'Cron','verify'=>'Verify','done'=>'Done'] as $k => $label):
            $i = array_search($k, $allSteps, true);
            $state2 = $i < $idx ? 'done' : ($i === $idx ? 'current' : 'todo');
        ?>
        <li class="flex items-center gap-2">
          <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold
            <?= $state2 === 'done' ? 'bg-emerald-600 text-white' : ($state2 === 'current' ? 'bg-brand-500 text-white' : 'bg-zinc-800 text-zinc-400') ?>">
            <?php if ($state2 === 'done'): ?><i data-lucide="check" class="w-3 h-3"></i><?php else: ?><?= $i+1 ?><?php endif ?>
          </span>
          <span class="<?= $state2 === 'todo' ? 'text-zinc-500' : 'text-zinc-200' ?>"><?= e($label) ?></span>
          <?php if ($k !== 'done'): ?><i data-lucide="chevron-right" class="w-3 h-3 text-zinc-700"></i><?php endif ?>
        </li>
        <?php endforeach ?>
      </ol>
    </div>

    <?php if ($flash): ?>
      <div class="mb-6 rounded-xl border <?= $flash['type'] === 'error' ? 'border-red-900/60 bg-red-950/40 text-red-200' : 'border-emerald-900/60 bg-emerald-950/40 text-emerald-200' ?> px-4 py-3 text-sm flex items-start gap-2">
        <i data-lucide="<?= $flash['type'] === 'error' ? 'alert-triangle' : 'check-circle-2' ?>" class="w-5 h-5 mt-0.5 shrink-0"></i>
        <div><?= e($flash['message']) ?></div>
      </div>
    <?php endif ?>

    <div class="rounded-2xl border border-zinc-800/70 bg-zinc-900/60 backdrop-blur shadow-card p-8">
      <?php
      switch ($step) {
        case 'welcome':       renderWelcome(); break;
        case 'requirements':  renderRequirements(); break;
        case 'database':      renderDatabase($token, $old); break;
        case 'app':           renderApp($token, $old, $state); break;
        case 'migrate':       renderMigrate($token, $state); break;
        case 'admin':         renderAdmin($token, $errors, $old); break;
        case 'cron':          renderCron($token); break;
        case 'verify':        renderVerify($token, $state); break;
        case 'done':          renderDone($state); break;
        case 'locked':        renderLocked(); break;
        default:              renderWelcome();
      }
      ?>
    </div>
    <p class="mt-6 text-center text-xs text-zinc-500">After installation, lock <code>storage/install.lock</code> and remove network access to <code>/install</code> for production.</p>
  </main>
</div>
<script>
window.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) window.lucide.createIcons();
});
</script>
</body>
</html>
<?php
    return (string) ob_get_clean();
}

function renderWelcome(): void { ?>
  <h1 class="text-2xl font-semibold tracking-tight">Welcome to StreamHub</h1>
  <p class="mt-2 text-zinc-400 text-sm">Modern PHP + MySQL adult/tube streaming CMS. The installer will check requirements, configure the database, run migrations, and create your superadmin.</p>
  <div class="mt-6 grid sm:grid-cols-3 gap-3">
    <div class="rounded-xl border border-zinc-800 p-4">
      <div class="flex items-center gap-2 text-sm font-medium"><i data-lucide="server" class="w-4 h-4 text-brand-500"></i> PHP 8+ &amp; MySQL 8+</div>
      <p class="text-xs text-zinc-500 mt-1">Runs on shared hosting (Apache / LiteSpeed / cPanel).</p>
    </div>
    <div class="rounded-xl border border-zinc-800 p-4">
      <div class="flex items-center gap-2 text-sm font-medium"><i data-lucide="shield" class="w-4 h-4 text-brand-500"></i> Secure by default</div>
      <p class="text-xs text-zinc-500 mt-1">CSRF, rate limiting, audit logs, encrypted secrets.</p>
    </div>
    <div class="rounded-xl border border-zinc-800 p-4">
      <div class="flex items-center gap-2 text-sm font-medium"><i data-lucide="alert-octagon" class="w-4 h-4 text-amber-500"></i> Adult content</div>
      <p class="text-xs text-zinc-500 mt-1">You are responsible for legal compliance in your jurisdiction.</p>
    </div>
  </div>
  <div class="mt-8 flex justify-end">
    <a href="/install?step=requirements" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium transition">
      Begin installation <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </a>
  </div>
<?php }

function renderRequirements(): void {
    $checks = checkRequirements();
    $allRequiredOk = true;
    foreach ($checks as $c) if (($c['required'] ?? false) && !$c['ok']) { $allRequiredOk = false; break; }
?>
  <h1 class="text-2xl font-semibold tracking-tight">Server requirements</h1>
  <p class="mt-2 text-zinc-400 text-sm">Required items must pass. Optional items are recommended.</p>
  <div class="mt-6 divide-y divide-zinc-800 rounded-xl border border-zinc-800 overflow-hidden">
    <?php foreach ($checks as $c): ?>
      <div class="flex items-center justify-between px-4 py-3 text-sm">
        <div class="flex items-center gap-3">
          <span class="w-2 h-2 rounded-full <?= $c['ok'] ? 'bg-emerald-500' : (($c['required'] ?? true) ? 'bg-red-500' : 'bg-amber-500') ?>"></span>
          <span class="font-medium"><?= e($c['label']) ?></span>
          <?php if (!($c['required'] ?? true)): ?><span class="text-[11px] text-zinc-500 ml-1">optional</span><?php endif ?>
        </div>
        <code class="text-xs text-zinc-400"><?= e((string) $c['value']) ?></code>
      </div>
    <?php endforeach ?>
  </div>
  <div class="mt-8 flex justify-between">
    <a href="/install?step=welcome" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
    <?php if ($allRequiredOk): ?>
      <a href="/install?step=database" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Continue <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    <?php else: ?>
      <button disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-800 text-zinc-500 text-sm font-medium cursor-not-allowed">Fix required items to continue</button>
    <?php endif ?>
  </div>
<?php }

function renderDatabase(string $token, array $old): void { ?>
  <h1 class="text-2xl font-semibold tracking-tight">Database connection</h1>
  <p class="mt-2 text-zinc-400 text-sm">Provide your MySQL credentials. The database must already exist.</p>
  <form method="post" action="/install?step=database" class="mt-6 grid sm:grid-cols-2 gap-4">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <?php inputField('db_host', 'Host', $old['db_host'] ?? '127.0.0.1') ?>
    <?php inputField('db_port', 'Port', $old['db_port'] ?? '3306') ?>
    <?php inputField('db_name', 'Database name', $old['db_name'] ?? '', true) ?>
    <?php inputField('db_user', 'Username', $old['db_user'] ?? '', true) ?>
    <?php passwordField('db_pass', 'Password') ?>
    <?php inputField('db_prefix', 'Table prefix (optional)', $old['db_prefix'] ?? '') ?>
    <div class="sm:col-span-2 flex justify-between mt-2">
      <a href="/install?step=requirements" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
      <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">
        Test &amp; continue <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </button>
    </div>
  </form>
<?php }

function renderApp(string $token, array $old, array $state): void {
    $appUrl = $old['app_url'] ?? ($state['app']['url'] ?? '');
    if ($appUrl === '') {
        $scheme = (Request::fromGlobals()->isHttps() ? 'https' : 'http');
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $appUrl = $scheme . '://' . $host;
    }
?>
  <h1 class="text-2xl font-semibold tracking-tight">Application setup</h1>
  <p class="mt-2 text-zinc-400 text-sm">Basic site identity. APP_KEY, ENCRYPTION_KEY, and CRON_TOKEN are generated automatically.</p>
  <form method="post" action="/install?step=app" class="mt-6 grid sm:grid-cols-2 gap-4">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <?php inputField('app_name', 'Site name', $old['app_name'] ?? ($state['app']['name'] ?? 'StreamHub'), true) ?>
    <?php inputField('app_url', 'Site URL', $appUrl, true) ?>
    <?php inputField('admin_path', 'Admin URL segment', $old['admin_path'] ?? ($state['app']['admin_path'] ?? 'admin'), true) ?>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Timezone</label>
      <select name="timezone" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
        <?php foreach (['UTC','Asia/Jakarta','Asia/Singapore','Asia/Tokyo','Europe/London','Europe/Berlin','America/New_York','America/Los_Angeles'] as $tz): ?>
          <option value="<?= e($tz) ?>" <?= ($old['timezone'] ?? $state['app']['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm text-zinc-300">
      <input type="checkbox" name="force_https" value="1" <?= !empty($old['force_https']) || !empty($state['app']['force_https']) ? 'checked' : '' ?>
        class="rounded border-zinc-700 bg-zinc-950 text-brand-500 focus:ring-brand-500">
      <span>Force HTTPS (set Secure cookies, HSTS)</span>
    </label>
    <div class="sm:col-span-2 flex justify-between mt-2">
      <a href="/install?step=database" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
      <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">
        Save &amp; continue <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </button>
    </div>
  </form>
<?php }

function renderMigrate(string $token, array $state): void {
    $sum = $state['migration_summary'] ?? null;
?>
  <h1 class="text-2xl font-semibold tracking-tight">Run migrations &amp; seeds</h1>
  <p class="mt-2 text-zinc-400 text-sm">This creates all tables defined in SQL Schema v1 plus the doodstream_sync_logs and content counter migrations, then applies default seed data.</p>
  <?php if ($sum): ?>
    <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
      <?php foreach ([['Applied', count($sum['applied']), 'emerald'], ['Skipped', count($sum['skipped']), 'zinc'], ['Seeds', count($sum['seed_applied']), 'emerald'], ['Seeds skipped', count($sum['seed_skipped']), 'zinc']] as [$lbl,$n,$c]): ?>
        <div class="rounded-xl border border-zinc-800 p-4">
          <div class="text-xs text-zinc-500"><?= e($lbl) ?></div>
          <div class="mt-1 text-xl font-semibold"><?= (int) $n ?></div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
  <form method="post" action="/install?step=migrate" class="mt-6">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <div class="flex justify-between items-center">
      <a href="/install?step=app" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
      <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">
        <i data-lucide="play" class="w-4 h-4"></i> Run migrations &amp; seeds
      </button>
    </div>
  </form>
<?php }

function renderAdmin(string $token, array $errors, array $old): void { ?>
  <h1 class="text-2xl font-semibold tracking-tight">Create superadmin</h1>
  <p class="mt-2 text-zinc-400 text-sm">This account will have full access to <code>/admin</code>. Only one role is supported: <strong>superadmin</strong>.</p>
  <form method="post" action="/install?step=admin" class="mt-6 grid sm:grid-cols-2 gap-4" x-data="{show:false}">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <?php inputField('username', 'Username', $old['username'] ?? '', true, $errors['username'] ?? null) ?>
    <?php inputField('email', 'Email', $old['email'] ?? '', true, $errors['email'] ?? null, 'email') ?>
    <div class="sm:col-span-2">
      <label class="block text-xs font-medium text-zinc-400 mb-1">Password</label>
      <div class="relative">
        <input :type="show ? 'text' : 'password'" name="password" minlength="10" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm pr-10">
        <button type="button" @click="show=!show" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-200">
          <i data-lucide="eye" class="w-4 h-4" x-show="!show"></i>
          <i data-lucide="eye-off" class="w-4 h-4" x-show="show" x-cloak></i>
        </button>
      </div>
      <p class="mt-1 text-xs text-zinc-500">At least 10 characters. Use a mix of letters, numbers, and symbols.</p>
      <?php if (!empty($errors['password'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['password']) ?></p><?php endif ?>
    </div>
    <div class="sm:col-span-2">
      <label class="block text-xs font-medium text-zinc-400 mb-1">Confirm password</label>
      <input :type="show ? 'text' : 'password'" name="password_confirmation" minlength="10" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <div class="sm:col-span-2 flex justify-between mt-2">
      <a href="/install?step=migrate" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
      <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">
        Create admin <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </button>
    </div>
  </form>
<?php }

function renderCron(string $token): void {
    $cronToken = (string) (env('CRON_TOKEN') ?? '');
    $appUrl    = rtrim((string) env('APP_URL', ''), '/');
    $cronUrl   = $appUrl . '/cron/run?token=' . $cronToken;
    $cliCmd    = 'php ' . STREAMHUB_BASE . '/cron/run.php --token=' . $cronToken;
?>
  <h1 class="text-2xl font-semibold tracking-tight">Cron &amp; queue setup</h1>
  <p class="mt-2 text-zinc-400 text-sm">Configure one of the following on your hosting (cPanel cron jobs, etc.). Recommended every 1 minute.</p>
  <div class="mt-6 grid sm:grid-cols-2 gap-4">
    <div class="rounded-xl border border-zinc-800 p-4" x-data="{copied:false}">
      <div class="flex items-center gap-2 text-sm font-medium"><i data-lucide="globe" class="w-4 h-4 text-brand-500"></i> URL cron (cPanel)</div>
      <p class="mt-1 text-xs text-zinc-500">Run this URL on a schedule.</p>
      <code class="block mt-3 text-xs bg-zinc-950 border border-zinc-800 rounded p-2 break-all"><?= e($cronUrl) ?></code>
      <button type="button" @click="navigator.clipboard.writeText('<?= e($cronUrl) ?>'); copied=true; setTimeout(()=>copied=false,1500)"
        class="mt-2 text-xs text-brand-400 hover:text-brand-300 inline-flex items-center gap-1">
        <i data-lucide="copy" class="w-3 h-3"></i> <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
      </button>
    </div>
    <div class="rounded-xl border border-zinc-800 p-4" x-data="{copied:false}">
      <div class="flex items-center gap-2 text-sm font-medium"><i data-lucide="terminal" class="w-4 h-4 text-brand-500"></i> CLI cron (SSH)</div>
      <p class="mt-1 text-xs text-zinc-500">Recommended when PHP CLI is available.</p>
      <code class="block mt-3 text-xs bg-zinc-950 border border-zinc-800 rounded p-2 break-all"><?= e($cliCmd) ?></code>
      <button type="button" @click="navigator.clipboard.writeText('<?= e($cliCmd) ?>'); copied=true; setTimeout(()=>copied=false,1500)"
        class="mt-2 text-xs text-brand-400 hover:text-brand-300 inline-flex items-center gap-1">
        <i data-lucide="copy" class="w-3 h-3"></i> <span x-text="copied ? 'Copied!' : 'Copy command'"></span>
      </button>
    </div>
  </div>
  <p class="mt-4 text-xs text-zinc-500">Keep the CRON_TOKEN private. It is required to authenticate cron requests. You can rotate it later from <code>.env</code>.</p>
  <form method="post" action="/install?step=cron" class="mt-8 flex justify-between">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <a href="/install?step=admin" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
    <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">
      I have configured cron <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </button>
  </form>
<?php }

function renderVerify(string $token, array $state): void {
    // Run a few read-only checks to display
    $checks = [];
    try {
        $db = Database::getInstance();
        $checks[] = ['label' => 'Database connection',         'ok' => true, 'value' => 'connected'];
        $checks[] = ['label' => 'Migrations applied',          'ok' => (int) $db->fetchValue('SELECT COUNT(*) FROM schema_migrations') > 0, 'value' => $db->fetchValue('SELECT COUNT(*) FROM schema_migrations') . ' rows'];
        $checks[] = ['label' => 'Default membership plans',    'ok' => (int) $db->fetchValue('SELECT COUNT(*) FROM membership_plans') >= 3, 'value' => 'free / premium / vip'];
        $checks[] = ['label' => 'Superadmin created',          'ok' => (int) $db->fetchValue('SELECT COUNT(*) FROM admin_users WHERE status="active"') > 0, 'value' => 'active'];
        $checks[] = ['label' => 'Default feature toggles',     'ok' => (int) $db->fetchValue('SELECT COUNT(*) FROM feature_toggles') > 0, 'value' => 'seeded'];
    } catch (\Throwable $e) {
        $checks[] = ['label' => 'Database connection', 'ok' => false, 'value' => $e->getMessage()];
    }
    $checks[] = ['label' => '.env file written', 'ok' => is_file(STREAMHUB_BASE . '/.env'), 'value' => is_file(STREAMHUB_BASE . '/.env') ? 'present' : 'missing'];
    $checks[] = ['label' => 'HTTPS enabled',     'ok' => Request::fromGlobals()->isHttps(), 'value' => Request::fromGlobals()->isHttps() ? 'yes' : 'recommended'];

    $allOk = true; foreach ($checks as $c) { if (!$c['ok']) { $allOk = false; break; } }
?>
  <h1 class="text-2xl font-semibold tracking-tight">Final verification</h1>
  <div class="mt-6 divide-y divide-zinc-800 rounded-xl border border-zinc-800 overflow-hidden">
    <?php foreach ($checks as $c): ?>
      <div class="flex items-center justify-between px-4 py-3 text-sm">
        <div class="flex items-center gap-3">
          <span class="w-2 h-2 rounded-full <?= $c['ok'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></span>
          <span><?= e($c['label']) ?></span>
        </div>
        <code class="text-xs text-zinc-500"><?= e((string) $c['value']) ?></code>
      </div>
    <?php endforeach ?>
  </div>
  <form method="post" action="/install?step=verify" class="mt-8 flex justify-between">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <a href="/install?step=cron" class="text-sm text-zinc-400 hover:text-zinc-200">Back</a>
    <button <?= $allOk ? '' : 'disabled' ?>
      class="inline-flex items-center gap-2 px-4 py-2 rounded-lg <?= $allOk ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-zinc-800 text-zinc-500 cursor-not-allowed' ?> text-sm font-medium">
      <i data-lucide="lock" class="w-4 h-4"></i> Lock installer &amp; finish
    </button>
  </form>
<?php }

function renderDone(array $state): void {
    $appUrl = rtrim((string) env('APP_URL', ''), '/');
    $adminPath = '/' . trim((string) (env('ADMIN_PATH') ?: 'admin'), '/');
?>
  <div class="text-center py-6">
    <div class="mx-auto w-14 h-14 rounded-full bg-emerald-600/15 border border-emerald-600/40 flex items-center justify-center">
      <i data-lucide="check" class="w-7 h-7 text-emerald-500"></i>
    </div>
    <h1 class="mt-4 text-2xl font-semibold tracking-tight">Installation complete</h1>
    <p class="mt-2 text-zinc-400 text-sm max-w-lg mx-auto">StreamHub is ready. The installer is now locked. Sign in to the admin panel to finish setting up content, ads, SEO and pages.</p>
  </div>
  <div class="mt-6 grid sm:grid-cols-2 gap-4">
    <a href="<?= e($appUrl . $adminPath . '/login') ?>" class="rounded-xl border border-zinc-800 hover:border-brand-600/60 p-5 transition group">
      <div class="flex items-center gap-2 text-sm font-medium text-zinc-200">
        <i data-lucide="shield-check" class="w-4 h-4 text-brand-500"></i> Admin login
      </div>
      <p class="mt-1 text-xs text-zinc-500">Open the superadmin panel.</p>
      <div class="mt-3 text-xs text-brand-400 group-hover:text-brand-300 inline-flex items-center gap-1">Open <i data-lucide="arrow-right" class="w-3 h-3"></i></div>
    </a>
    <a href="<?= e($appUrl . '/') ?>" class="rounded-xl border border-zinc-800 hover:border-brand-600/60 p-5 transition group">
      <div class="flex items-center gap-2 text-sm font-medium text-zinc-200">
        <i data-lucide="globe" class="w-4 h-4 text-brand-500"></i> Public site
      </div>
      <p class="mt-1 text-xs text-zinc-500">Open the homepage.</p>
      <div class="mt-3 text-xs text-brand-400 group-hover:text-brand-300 inline-flex items-center gap-1">Open <i data-lucide="arrow-right" class="w-3 h-3"></i></div>
    </a>
  </div>
  <div class="mt-6 rounded-xl border border-amber-900/40 bg-amber-950/30 p-4 text-xs text-amber-200/90 flex gap-2">
    <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
    <div>
      Next steps: configure Doodstream API in Admin → API Manager (encrypted), edit legal pages (DMCA / Privacy / Terms), and configure cron as shown in the previous step.
    </div>
  </div>
<?php }

function renderLocked(): void { ?>
  <div class="text-center py-8">
    <div class="mx-auto w-14 h-14 rounded-full bg-zinc-800 flex items-center justify-center">
      <i data-lucide="lock" class="w-6 h-6 text-zinc-400"></i>
    </div>
    <h1 class="mt-4 text-2xl font-semibold tracking-tight">Installer is locked</h1>
    <p class="mt-2 text-zinc-400 text-sm">This site has already been installed. To reinstall, manually delete <code>storage/install.lock</code> on the server.</p>
  </div>
<?php }

function inputField(string $name, string $label, string $value = '', bool $required = false, ?string $error = null, string $type = 'text'): void { ?>
  <div>
    <label class="block text-xs font-medium text-zinc-400 mb-1"><?= e($label) ?><?php if ($required): ?> <span class="text-red-500">*</span><?php endif ?></label>
    <input type="<?= e($type) ?>" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?> value="<?= e($value) ?>"
      class="w-full rounded-lg bg-zinc-950 border <?= $error ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600">
    <?php if ($error): ?><p class="mt-1 text-xs text-red-400"><?= e($error) ?></p><?php endif ?>
  </div>
<?php }

function passwordField(string $name, string $label): void { ?>
  <div>
    <label class="block text-xs font-medium text-zinc-400 mb-1"><?= e($label) ?></label>
    <input type="password" name="<?= e($name) ?>" autocomplete="off" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
  </div>
<?php }
