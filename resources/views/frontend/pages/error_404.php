<?php ob_start(); ?>
<div class="max-w-md mx-auto text-center py-16">
  <div class="mx-auto w-14 h-14 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center"><i data-lucide="search-x" class="w-6 h-6 text-zinc-400"></i></div>
  <h1 class="mt-4 text-3xl font-semibold tracking-tight">404</h1>
  <p class="mt-2 text-sm text-zinc-400">We couldn't find that page.</p>
  <a href="/" class="mt-6 inline-flex px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Back to home</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
