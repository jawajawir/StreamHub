<?php /** @var array $rows */
ob_start(); ?>
<header class="mb-4"><h1 class="text-2xl font-semibold tracking-tight">Favorites</h1></header>
<?php if (empty($rows)): ?>
  <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center text-sm text-zinc-400">No favorites yet.</div>
<?php else:
  $items = $rows; include __DIR__ . '/../../components/grid.php';
endif ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
