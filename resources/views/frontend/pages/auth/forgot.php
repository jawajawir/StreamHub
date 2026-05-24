<?php ob_start(); ?>
<div class="max-w-md mx-auto">
  <h1 class="text-2xl font-semibold tracking-tight">Forgot password</h1>
  <p class="mt-1 text-sm text-zinc-400">Enter your account email. If it exists, we'll send a reset link.</p>
  <form method="post" action="/forgot-password" class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 space-y-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Email</label>
      <input name="email" type="email" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <button class="w-full px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Send reset link</button>
    <p class="text-center text-xs text-zinc-400"><a href="/login" class="hover:underline">Back to sign in</a></p>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
