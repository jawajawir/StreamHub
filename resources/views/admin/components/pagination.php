<?php /** @var int $page @var int $pages @var string $baseUrl (already includes ?...) */
if (($pages ?? 1) < 2) return;
$sep = str_contains($baseUrl, '?') ? '&' : '?';
?>
<nav class="mt-4 flex items-center justify-end gap-1 text-xs">
  <?php if ($page > 1): ?><a class="px-2.5 py-1 rounded border border-zinc-800 hover:bg-zinc-800/40" href="<?= e($baseUrl . $sep . 'page=' . ($page - 1)) ?>">Prev</a><?php endif ?>
  <span class="px-2 text-zinc-500">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
  <?php if ($page < $pages): ?><a class="px-2.5 py-1 rounded border border-zinc-800 hover:bg-zinc-800/40" href="<?= e($baseUrl . $sep . 'page=' . ($page + 1)) ?>">Next</a><?php endif ?>
</nav>
