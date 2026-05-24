<?php /** @var array $list @var string $baseUrl */
$pages = (int) ($list['pages'] ?? 1);
$current = (int) ($list['page'] ?? 1);
if ($pages < 2) return;
$separator = str_contains($baseUrl, '?') ? '&' : '?';
$start = max(1, $current - 2); $end = min($pages, $current + 2);
?>
<nav class="mt-6 flex items-center justify-center gap-1 text-sm">
  <?php if ($current > 1): ?><a class="px-3 py-1.5 rounded border border-zinc-800 hover:bg-zinc-800/50" href="<?= e($baseUrl . $separator . 'page=' . ($current - 1)) ?>">Prev</a><?php endif ?>
  <?php for ($i = $start; $i <= $end; $i++): ?>
    <a class="px-3 py-1.5 rounded border <?= $i === $current ? 'border-brand-600 bg-brand-600/10 text-brand-300' : 'border-zinc-800 hover:bg-zinc-800/50' ?>" href="<?= e($baseUrl . $separator . 'page=' . $i) ?>"><?= $i ?></a>
  <?php endfor ?>
  <?php if ($current < $pages): ?><a class="px-3 py-1.5 rounded border border-zinc-800 hover:bg-zinc-800/50" href="<?= e($baseUrl . $separator . 'page=' . ($current + 1)) ?>">Next</a><?php endif ?>
</nav>
