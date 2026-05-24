<?php /** @var array $auth_admin @var string $title */
$title    = $title ?? 'Admin';
$flash    = flash();
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · <?= e($app_name ?? 'StreamHub') ?> Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { darkMode: 'class', theme: { extend: { colors: { brand: { 500: '#ef4444', 600: '#dc2626', 400: '#f87171' } } } } };
</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<style>
[x-cloak]{display:none}
body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.scrollbar-thin::-webkit-scrollbar{width:6px;height:6px}
.scrollbar-thin::-webkit-scrollbar-thumb{background:#3f3f46;border-radius:3px}
</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100" x-data="{ sidebar: window.innerWidth >= 1024, mobileOpen: false }">

<aside :class="sidebar ? 'lg:w-64' : 'lg:w-16'"
       class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0 transition-all duration-200 bg-zinc-900/80 backdrop-blur border-r border-zinc-800/70 flex flex-col"
       :class="mobileOpen ? '!translate-x-0' : ''">
  <div class="h-14 flex items-center px-4 border-b border-zinc-800/70 gap-2">
    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shrink-0">
      <i data-lucide="play" class="w-4 h-4 text-white"></i>
    </div>
    <div class="flex-1 overflow-hidden" x-show="sidebar || mobileOpen" x-cloak>
      <div class="text-sm font-semibold tracking-tight"><?= e($app_name ?? 'StreamHub') ?></div>
      <div class="text-[11px] text-zinc-500">Admin panel</div>
    </div>
    <button @click="mobileOpen=false" class="lg:hidden text-zinc-400 hover:text-zinc-200">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
  </div>
  <nav class="flex-1 overflow-y-auto scrollbar-thin py-3">
    <?php
      $nav = [
        ['path' => '',                   'label' => 'Dashboard',           'icon' => 'gauge'],
        ['path' => 'users',              'label' => 'User Manager',        'icon' => 'users'],
        ['path' => 'membership-requests','label' => 'Membership Requests', 'icon' => 'mail-question'],
        ['path' => 'videos',             'label' => 'Video Manager',       'icon' => 'film'],
        ['heading' => 'Taxonomy'],
        ['path' => 'categories',         'label' => 'Categories',          'icon' => 'folder-tree'],
        ['path' => 'tags',               'label' => 'Tags',                'icon' => 'tag'],
        ['path' => 'performers',         'label' => 'Performers',          'icon' => 'user-circle'],
        ['path' => 'studios',            'label' => 'Studios',             'icon' => 'building-2'],
        ['path' => 'series',             'label' => 'Series',              'icon' => 'list-tree'],
        ['heading' => 'Integrations'],
        ['path' => 'api/doodstream',     'label' => 'API Manager',         'icon' => 'plug'],
        ['heading' => 'Content'],
        ['path' => 'pages',              'label' => 'Pages',               'icon' => 'file-text'],
        ['heading' => 'System'],
        ['path' => 'settings',           'label' => 'Settings',            'icon' => 'settings'],
        ['path' => 'system/audit-logs',  'label' => 'Audit Logs',          'icon' => 'history'],
        ['path' => 'system/security-events','label' => 'Security Events',  'icon' => 'shield-alert'],
      ];
      $cur = ($_SERVER['REQUEST_URI'] ?? '/');
    ?>
    <?php foreach ($nav as $n): ?>
      <?php if (!empty($n['heading'])): ?>
        <div class="px-4 pt-4 pb-1 text-[10px] uppercase tracking-wider text-zinc-500" x-show="sidebar || mobileOpen" x-cloak><?= e($n['heading']) ?></div>
      <?php else:
        $href   = admin_url($n['path']);
        $active = $cur === $href || str_starts_with($cur, $href . '/');
      ?>
        <a href="<?= e($href) ?>" class="mx-2 my-0.5 flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition <?= $active ? 'bg-brand-600/20 text-brand-300 border border-brand-600/30' : 'text-zinc-300 hover:bg-zinc-800/60' ?>">
          <i data-lucide="<?= e($n['icon']) ?>" class="w-4 h-4 shrink-0"></i>
          <span x-show="sidebar || mobileOpen" x-cloak><?= e($n['label']) ?></span>
        </a>
      <?php endif ?>
    <?php endforeach ?>
  </nav>
  <div class="p-3 border-t border-zinc-800/70" x-show="sidebar || mobileOpen" x-cloak>
    <form method="post" action="<?= e(admin_url('logout')) ?>" class="flex items-center justify-between text-xs">
      <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-full bg-zinc-800 flex items-center justify-center"><i data-lucide="user" class="w-3.5 h-3.5 text-zinc-300"></i></div>
        <div>
          <div class="font-medium leading-none"><?= e($auth_admin['username'] ?? '—') ?></div>
          <div class="text-zinc-500 leading-none mt-0.5">superadmin</div>
        </div>
      </div>
      <button title="Sign out" class="p-1.5 rounded text-zinc-400 hover:text-red-400 hover:bg-red-950/30">
        <i data-lucide="log-out" class="w-4 h-4"></i>
      </button>
    </form>
  </div>
</aside>

<div :class="sidebar ? 'lg:ml-64' : 'lg:ml-16'" class="transition-all">
  <header class="sticky top-0 z-30 h-14 bg-zinc-950/85 backdrop-blur border-b border-zinc-800/70 flex items-center px-4 gap-3">
    <button @click="if (window.innerWidth < 1024) { mobileOpen = !mobileOpen } else { sidebar = !sidebar }"
            class="text-zinc-400 hover:text-zinc-100 p-1.5 rounded hover:bg-zinc-800/60">
      <i data-lucide="menu" class="w-5 h-5"></i>
    </button>
    <div class="flex-1 max-w-lg">
      <div class="relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500"></i>
        <input class="w-full pl-9 pr-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-800 text-sm placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600" placeholder="Quick search… (use the page search inside each module)">
      </div>
    </div>
    <a href="/" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-100">
      <i data-lucide="external-link" class="w-3.5 h-3.5"></i> View site
    </a>
  </header>

  <main class="p-4 lg:p-6">
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
</div>

<div x-show="mobileOpen" x-cloak @click="mobileOpen=false" class="fixed inset-0 z-30 bg-black/60 lg:hidden"></div>

<script>
window.addEventListener('DOMContentLoaded', () => { if (window.lucide) window.lucide.createIcons(); });
document.addEventListener('alpine:initialized', () => { if (window.lucide) window.lucide.createIcons(); });
</script>
</body>
</html>
