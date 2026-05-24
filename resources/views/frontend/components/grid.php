<?php /** @var array $items */
if (empty($items)) {
    echo '<div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center text-sm text-zinc-400">No content yet.</div>';
    return;
}
?>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
  <?php foreach ($items as $item): include __DIR__ . '/card.php'; endforeach ?>
</div>
