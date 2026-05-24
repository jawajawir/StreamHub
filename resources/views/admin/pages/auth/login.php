<?php /** @var string $title */
$flash = flash();
$errors = \App\Core\Session::instance()->pull('_errors', []);
$old    = \App\Core\Session::instance()->pull('_old_input', []);
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · <?= e($app_name ?? 'StreamHub') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:{500:'#ef4444',600:'#dc2626'}}}}};</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>[x-cloak]{display:none}body{font-family:ui-sans-serif,system-ui}</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 flex items-center justify-center p-4">
<div class="w-full max-w-md">
  <div class="text-center mb-6">
    <div class="mx-auto w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center"><i data-lucide="shield" class="w-6 h-6 text-white"></i></div>
    <h1 class="mt-4 text-2xl font-semibold tracking-tight">Admin sign in</h1>
    <p class="text-sm text-zinc-400">Superadmin only</p>
  </div>
  <?php if (!empty($flash['error'])): ?>
    <div class="mb-4 rounded-lg border border-red-900/60 bg-red-950/40 text-red-200 px-4 py-3 text-sm"><?= e($flash['error']) ?></div>
  <?php endif ?>
  <form method="post" action="<?= e(admin_url('login')) ?>" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 space-y-4" x-data="{show:false}">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Username or email</label>
      <input name="identifier" required autofocus value="<?= e($old['identifier'] ?? '') ?>"
        class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['identifier']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600">
      <?php if (!empty($errors['identifier'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['identifier']) ?></p><?php endif ?>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Password</label>
      <div class="relative">
        <input :type="show?'text':'password'" name="password" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm pr-10 focus:outline-none focus:ring-2 focus:ring-brand-600">
        <button type="button" @click="show=!show" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-200">
          <i data-lucide="eye" class="w-4 h-4" x-show="!show"></i>
          <i data-lucide="eye-off" class="w-4 h-4" x-show="show" x-cloak></i>
        </button>
      </div>
    </div>
    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Sign in <i data-lucide="arrow-right" class="w-4 h-4"></i></button>
  </form>
  <p class="mt-4 text-center text-xs text-zinc-500"><a href="/" class="hover:text-zinc-300">&larr; Back to site</a></p>
</div>
<script>window.addEventListener('DOMContentLoaded',()=>{if(window.lucide)window.lucide.createIcons();});</script>
</body>
</html>
