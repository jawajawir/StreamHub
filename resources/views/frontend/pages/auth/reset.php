<?php /** @var string $token */
$errors = \App\Core\Session::instance()->pull('_errors', []);
ob_start(); ?>
<div class="max-w-md mx-auto">
  <h1 class="text-2xl font-semibold tracking-tight">Set a new password</h1>
  <form method="post" action="/reset-password" class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 space-y-4" x-data="{show:false}">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">New password</label>
      <input :type="show?'text':'password'" name="password" required minlength="10" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
      <?php if (!empty($errors['password'])): ?><p class="mt-1 text-xs text-red-400"><?= e($errors['password']) ?></p><?php endif ?>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Confirm</label>
      <input :type="show?'text':'password'" name="password_confirmation" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <label class="inline-flex items-center gap-2 text-xs text-zinc-400"><input type="checkbox" @change="show=!show" class="rounded border-zinc-700 bg-zinc-950"> Show password</label>
    <button class="w-full px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Reset password</button>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
