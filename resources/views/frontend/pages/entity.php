<?php /** Performer / Studio / Series page. */
$items = $list['items'] ?? [];
ob_start(); ?>
<header class="rounded-2xl border border-zinc-800 bg-gradient-to-br from-zinc-900 to-zinc-950 p-6 mb-6 flex items-start gap-4">
  <?php if (!empty($avatar)): ?>
    <img src="<?= e($avatar) ?>" alt="" class="w-20 h-20 rounded-xl object-cover">
  <?php else: ?>
    <div class="w-20 h-20 rounded-xl bg-zinc-800 flex items-center justify-center"><i data-lucide="user" class="w-8 h-8 text-zinc-500"></i></div>
  <?php endif ?>
  <div class="flex-1 min-w-0">
    <h1 class="text-2xl font-semibold tracking-tight"><?= e($heading) ?></h1>
    <?php if (!empty($subheading)): ?><p class="mt-2 text-sm text-zinc-400 leading-relaxed"><?= e($subheading) ?></p><?php endif ?>
    <p class="mt-2 text-xs text-zinc-500"><?= number_format((int) ($list['total'] ?? 0)) ?> videos</p>
  </div>
</header>
<?php include __DIR__ . '/../components/grid.php' ?>
<?php include __DIR__ . '/../components/pagination.php' ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
