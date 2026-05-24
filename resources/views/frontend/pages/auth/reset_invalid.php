<?php ob_start(); ?>
<div class="max-w-md mx-auto text-center py-12">
  <div class="mx-auto w-12 h-12 rounded-full bg-red-950/40 border border-red-900/60 flex items-center justify-center"><i data-lucide="alert-triangle" class="w-6 h-6 text-red-400"></i></div>
  <h1 class="mt-4 text-xl font-semibold">Reset link is invalid or expired</h1>
  <p class="mt-2 text-sm text-zinc-400">Request a new <a href="/forgot-password" class="text-brand-400 hover:underline">password reset</a>.</p>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
