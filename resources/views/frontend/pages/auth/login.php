<?php
$flash  = flash();
$errors = \App\Core\Session::instance()->pull('_errors', []);
$old    = \App\Core\Session::instance()->pull('_old_input', []);
ob_start(); ?>
<div class="max-w-md mx-auto">
  <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
  <p class="mt-1 text-sm text-zinc-400">Welcome back. Enter your credentials.</p>
  <form method="post" action="/login" class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 space-y-4" x-data="{show:false}">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Username or email</label>
      <input name="identifier" required autofocus value="<?= e($old['identifier'] ?? '') ?>" class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['identifier']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
      <?php if (!empty($errors['identifier'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['identifier']) ?></p><?php endif ?>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Password</label>
      <div class="relative">
        <input :type="show?'text':'password'" name="password" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm pr-10">
        <button type="button" @click="show=!show" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500"><i data-lucide="eye" class="w-4 h-4" x-show="!show"></i><i data-lucide="eye-off" class="w-4 h-4" x-show="show" x-cloak></i></button>
      </div>
    </div>
    <button class="w-full px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Sign in</button>
    <div class="flex justify-between text-xs text-zinc-400">
      <a href="/forgot-password" class="hover:text-zinc-200">Forgot password?</a>
      <?php if (\App\Services\FeatureToggleService::enabled('registration')): ?>
        <a href="/register" class="hover:text-zinc-200">Create account</a>
      <?php endif ?>
    </div>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
