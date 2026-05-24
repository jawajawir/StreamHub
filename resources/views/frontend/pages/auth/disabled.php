<?php ob_start(); ?>
<div class="max-w-md mx-auto text-center py-12">
  <div class="mx-auto w-12 h-12 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center"><i data-lucide="user-x" class="w-6 h-6 text-zinc-400"></i></div>
  <h1 class="mt-4 text-xl font-semibold">Registration is currently closed</h1>
  <p class="mt-2 text-sm text-zinc-400">Check back later, or <a href="/login" class="text-brand-400 hover:underline">sign in</a> if you already have an account.</p>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
