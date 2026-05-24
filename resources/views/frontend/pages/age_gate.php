<?php
$siteName = (string) (\App\Services\SettingService::get('site', 'name', $app_name ?? 'StreamHub') ?? 'StreamHub');
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Age verification · <?= e($siteName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:{500:'#ef4444',600:'#dc2626'}}}}};</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<style>body{font-family:ui-sans-serif,system-ui}</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 flex items-center justify-center p-4">
<div class="max-w-md w-full">
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 text-center">
    <div class="mx-auto w-12 h-12 rounded-xl bg-brand-600/15 border border-brand-600/30 flex items-center justify-center"><i data-lucide="shield-alert" class="w-6 h-6 text-brand-500"></i></div>
    <h1 class="mt-4 text-2xl font-semibold tracking-tight">Adults only</h1>
    <p class="mt-2 text-sm text-zinc-400">This site contains adult content. By entering, you confirm that you are at least 18 years of age and that adult content is legal where you live.</p>
    <form method="post" action="/age-gate/accept" class="mt-6 space-y-3">
      <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
      <input type="hidden" name="confirm" value="1">
      <button class="w-full px-4 py-3 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold">I am 18+ — Enter</button>
      <a href="https://www.google.com" rel="noopener" class="block w-full px-4 py-3 rounded-lg border border-zinc-800 hover:bg-zinc-800/40 text-sm text-zinc-300">I am under 18 — Leave</a>
    </form>
    <p class="mt-4 text-[11px] text-zinc-500">By entering you agree to our <a href="/page/terms" class="hover:underline">Terms</a>, <a href="/page/privacy" class="hover:underline">Privacy</a>, and <a href="/page/age-disclaimer" class="hover:underline">Age Disclaimer</a>.</p>
  </div>
</div>
<script>window.addEventListener('DOMContentLoaded',()=>{if(window.lucide)window.lucide.createIcons();});</script>
</body>
</html>
