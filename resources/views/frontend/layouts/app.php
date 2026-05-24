<?php /** @var string $title @var array|null $auth_user */
$title = $title ?? ($app_name ?? 'StreamHub');
$siteName = (string) (\App\Services\SettingService::get('site', 'name', $app_name ?? 'StreamHub') ?? 'StreamHub');
$tagline  = (string) (\App\Services\SettingService::get('site', 'tagline', '') ?? '');
$flash = flash();
$canonical = url(ltrim(parse_url(current_url(), PHP_URL_PATH) ?? '/', '/'));
$metaDesc  = $metaDesc ?? (string) (\App\Services\SettingService::get('site', 'description', '') ?? '');
$ogImage   = $ogImage  ?? '';
$robots    = $robots   ?? 'index, follow';
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="<?= e($robots) ?>">
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif ?>
<meta property="og:type" content="website">
<title><?= e($title) ?> · <?= e($siteName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:{400:'#f87171',500:'#ef4444',600:'#dc2626'}}}}};</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>[x-cloak]{display:none}body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 pb-20 md:pb-0">

<header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur border-b border-zinc-800/70" x-data="{ mobileSearch:false, account:false }">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center gap-3">
    <a href="/" class="flex items-center gap-2 shrink-0">
      <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center"><i data-lucide="play" class="w-4 h-4 text-white"></i></div>
      <span class="text-sm font-semibold tracking-tight"><?= e($siteName) ?></span>
    </a>
    <nav class="hidden md:flex items-center gap-1 text-sm">
      <a href="/latest"      class="px-3 py-1.5 rounded hover:bg-zinc-800/60 text-zinc-300">Latest</a>
      <a href="/trending"    class="px-3 py-1.5 rounded hover:bg-zinc-800/60 text-zinc-300">Trending</a>
      <a href="/most-viewed" class="px-3 py-1.5 rounded hover:bg-zinc-800/60 text-zinc-300">Most Viewed</a>
      <a href="/membership"  class="px-3 py-1.5 rounded hover:bg-zinc-800/60 text-zinc-300">Membership</a>
    </nav>
    <form action="/search" method="get" class="hidden md:block flex-1 max-w-md ml-auto">
      <div class="relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500"></i>
        <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search" class="w-full pl-9 pr-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-800 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
      </div>
    </form>
    <button @click="mobileSearch=!mobileSearch" class="md:hidden text-zinc-300 hover:text-white p-1.5"><i data-lucide="search" class="w-5 h-5"></i></button>

    <?php if ($auth_user): ?>
      <div class="relative" @click.outside="account=false">
        <button @click="account=!account" class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-zinc-800/60">
          <div class="w-7 h-7 rounded-full bg-zinc-800 flex items-center justify-center"><i data-lucide="user" class="w-3.5 h-3.5"></i></div>
          <span class="hidden sm:inline text-sm"><?= e($auth_user['display_name'] ?: $auth_user['username']) ?></span>
        </button>
        <div x-show="account" x-cloak x-transition class="absolute right-0 mt-2 w-56 rounded-xl bg-zinc-900 border border-zinc-800 shadow-xl text-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-zinc-800">
            <div class="font-medium"><?= e($auth_user['username']) ?></div>
            <div class="text-[11px] text-zinc-500 mt-0.5"><?= e(strtoupper($auth_user['membership_tier'])) ?> member</div>
          </div>
          <a href="/account"          class="block px-4 py-2 hover:bg-zinc-800">Dashboard</a>
          <a href="/account/favorites" class="block px-4 py-2 hover:bg-zinc-800">Favorites</a>
          <a href="/account/history"   class="block px-4 py-2 hover:bg-zinc-800">Watch history</a>
          <a href="/membership"        class="block px-4 py-2 hover:bg-zinc-800">Membership</a>
          <form method="post" action="/logout" class="block">
            <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
            <button class="w-full text-left px-4 py-2 hover:bg-zinc-800 text-red-400 border-t border-zinc-800">Sign out</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="flex items-center gap-2">
        <a href="/login"    class="hidden sm:inline-flex px-3 py-1.5 rounded text-sm text-zinc-300 hover:bg-zinc-800/60">Sign in</a>
        <a href="/register" class="px-3 py-1.5 rounded text-sm font-medium bg-brand-600 hover:bg-brand-500 text-white">Join</a>
      </div>
    <?php endif ?>
  </div>
  <div x-show="mobileSearch" x-cloak class="md:hidden border-t border-zinc-800/70 px-4 py-3">
    <form action="/search" method="get">
      <input name="q" autofocus placeholder="Search" class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-sm">
    </form>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">
  <?php if (!empty($flash['success'])): ?>
    <div class="mb-4 rounded-lg border border-emerald-900/60 bg-emerald-950/40 text-emerald-200 px-4 py-3 text-sm flex items-start gap-2">
      <i data-lucide="check-circle-2" class="w-4 h-4 mt-0.5 shrink-0"></i><div><?= e($flash['success']) ?></div>
    </div>
  <?php endif ?>
  <?php if (!empty($flash['error'])): ?>
    <div class="mb-4 rounded-lg border border-red-900/60 bg-red-950/40 text-red-200 px-4 py-3 text-sm flex items-start gap-2">
      <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i><div><?= e($flash['error']) ?></div>
    </div>
  <?php endif ?>
  <?= $content ?? '' ?>
</main>

<footer class="border-t border-zinc-800/70 mt-12">
  <div class="max-w-7xl mx-auto px-4 py-8 text-sm text-zinc-400 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div>
      <div class="text-zinc-200 font-semibold"><?= e($siteName) ?></div>
      <p class="mt-2 text-xs text-zinc-500"><?= e($tagline) ?></p>
    </div>
    <div>
      <div class="text-zinc-200 text-sm font-medium mb-2">Site</div>
      <ul class="space-y-1 text-xs">
        <li><a class="hover:text-white" href="/latest">Latest</a></li>
        <li><a class="hover:text-white" href="/trending">Trending</a></li>
        <li><a class="hover:text-white" href="/most-viewed">Most viewed</a></li>
        <li><a class="hover:text-white" href="/membership">Membership</a></li>
      </ul>
    </div>
    <div>
      <div class="text-zinc-200 text-sm font-medium mb-2">Legal</div>
      <ul class="space-y-1 text-xs">
        <li><a class="hover:text-white" href="/page/dmca">DMCA</a></li>
        <li><a class="hover:text-white" href="/page/privacy">Privacy</a></li>
        <li><a class="hover:text-white" href="/page/terms">Terms</a></li>
        <li><a class="hover:text-white" href="/page/age-disclaimer">Age Disclaimer</a></li>
        <li><a class="hover:text-white" href="/page/content-disclaimer">Content Disclaimer</a></li>
      </ul>
    </div>
    <div>
      <div class="text-zinc-200 text-sm font-medium mb-2">Other</div>
      <ul class="space-y-1 text-xs">
        <li><a class="hover:text-white" href="/page/contact">Contact</a></li>
        <li><a class="hover:text-white" href="/page/faq">FAQ</a></li>
        <li><a class="hover:text-white" href="/page/advertise">Advertise</a></li>
        <li><a class="hover:text-white" href="/page/webmaster">Webmaster / Partner</a></li>
        <li><a class="hover:text-white" href="/sitemap.xml">Sitemap</a></li>
      </ul>
    </div>
  </div>
  <div class="border-t border-zinc-800/70 py-4 text-center text-xs text-zinc-500">
    &copy; <?= date('Y') ?> <?= e($siteName) ?>. Adults 18+ only.
  </div>
</footer>

<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-zinc-950/95 backdrop-blur border-t border-zinc-800/70">
  <div class="grid grid-cols-5 text-[11px] text-zinc-400">
    <a href="/"           class="flex flex-col items-center justify-center py-2 gap-0.5 hover:text-white"><i data-lucide="home"   class="w-5 h-5"></i>Home</a>
    <a href="/search"     class="flex flex-col items-center justify-center py-2 gap-0.5 hover:text-white"><i data-lucide="search" class="w-5 h-5"></i>Search</a>
    <a href="/latest"     class="flex flex-col items-center justify-center py-2 gap-0.5 hover:text-white"><i data-lucide="layers" class="w-5 h-5"></i>Latest</a>
    <a href="<?= $auth_user ? '/account/favorites' : '/login?next=/account/favorites' ?>" class="flex flex-col items-center justify-center py-2 gap-0.5 hover:text-white"><i data-lucide="heart"  class="w-5 h-5"></i>Saved</a>
    <a href="<?= $auth_user ? '/account' : '/login' ?>" class="flex flex-col items-center justify-center py-2 gap-0.5 hover:text-white"><i data-lucide="user"   class="w-5 h-5"></i>Account</a>
  </div>
</nav>

<script>
window.addEventListener('DOMContentLoaded', () => { if (window.lucide) window.lucide.createIcons(); });
document.addEventListener('alpine:initialized', () => { if (window.lucide) window.lucide.createIcons(); });
</script>
</body>
</html>
