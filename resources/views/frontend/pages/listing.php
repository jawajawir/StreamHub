<?php /** @var array $list @var string $baseUrl @var string $heading @var ?string $subheading @var ?string $sort */
$items = $list['items'] ?? [];
ob_start(); ?>
<header class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight"><?= e($heading ?? 'Browse') ?></h1>
  <?php if (!empty($subheading)): ?><p class="mt-1 text-sm text-zinc-400 max-w-2xl"><?= e($subheading) ?></p><?php endif ?>
  <?php if (!empty($q ?? '')): ?>
    <p class="mt-1 text-xs text-zinc-500"><?= number_format((int) ($list['total'] ?? 0)) ?> result(s)</p>
  <?php endif ?>
</header>
<?php include __DIR__ . '/../components/grid.php' ?>
<?php include __DIR__ . '/../components/pagination.php' ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
