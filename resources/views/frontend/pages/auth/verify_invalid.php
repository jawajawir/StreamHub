<?php ob_start(); ?>
<div class="max-w-md mx-auto text-center py-12">
  <div class="mx-auto w-12 h-12 rounded-full bg-red-950/40 border border-red-900/60 flex items-center justify-center"><i data-lucide="x-circle" class="w-6 h-6 text-red-400"></i></div>
  <h1 class="mt-4 text-xl font-semibold">Verification link is invalid or expired</h1>
  <p class="mt-2 text-sm text-zinc-400">Sign in to request a new verification email.</p>
  <a href="/login" class="mt-4 inline-flex px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Sign in</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
