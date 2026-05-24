<?php
$errors = \App\Core\Session::instance()->pull('_errors', []);
$old    = \App\Core\Session::instance()->pull('_old_input', []);
ob_start(); ?>
<div class="max-w-md mx-auto">
  <h1 class="text-2xl font-semibold tracking-tight">Create your account</h1>
  <p class="mt-1 text-sm text-zinc-400">Free account. You can upgrade to Premium or VIP later.</p>
  <form method="post" action="/register" class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 space-y-4" x-data="{show:false}">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Username</label>
      <input name="username" required autofocus value="<?= e($old['username'] ?? '') ?>" minlength="3" maxlength="80" pattern="[a-zA-Z0-9_.\-]+"
        class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['username']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
      <?php if (!empty($errors['username'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['username']) ?></p><?php endif ?>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Email</label>
      <input name="email" type="email" required value="<?= e($old['email'] ?? '') ?>"
        class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['email']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
      <?php if (!empty($errors['email'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['email']) ?></p><?php endif ?>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Password</label>
      <div class="relative">
        <input :type="show?'text':'password'" name="password" required minlength="10" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm pr-10">
        <button type="button" @click="show=!show" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500"><i data-lucide="eye" class="w-4 h-4" x-show="!show"></i><i data-lucide="eye-off" class="w-4 h-4" x-show="show" x-cloak></i></button>
      </div>
      <p class="mt-1 text-xs text-zinc-500">At least 10 characters.</p>
      <?php if (!empty($errors['password'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['password']) ?></p><?php endif ?>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Confirm password</label>
      <input :type="show?'text':'password'" name="password_confirmation" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <p class="text-xs text-zinc-500">By creating an account, you confirm you are 18+ and agree to the <a href="/page/terms" class="text-brand-400 hover:underline">Terms</a> and <a href="/page/privacy" class="text-brand-400 hover:underline">Privacy Policy</a>.</p>
    <button class="w-full px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Create account</button>
    <p class="text-center text-xs text-zinc-400">Already have an account? <a href="/login" class="text-brand-400 hover:underline">Sign in</a></p>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
