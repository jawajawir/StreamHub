<?php /** @var array $rows */
ob_start();
$heading = 'Tags'; $sub = count($rows) . ' tags';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
    <div class="p-3 flex flex-wrap gap-1 max-h-[60vh] overflow-y-auto">
      <?php if (empty($rows)): ?><div class="text-zinc-500 text-sm px-2 py-4">No tags yet.</div><?php endif ?>
      <?php foreach ($rows as $r): ?>
        <div class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-zinc-800 bg-zinc-900/50 text-xs">
          <span><?= e($r['name']) ?></span>
          <span class="text-zinc-500">· <?= (int) $r['content_count'] ?></span>
          <form method="post" action="<?= e(admin_url('tags/' . $r['id'] . '/delete')) ?>" class="inline" onsubmit="return confirm('Delete tag?')">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="text-red-400 hover:text-red-300 text-[10px] ml-1">×</button>
          </form>
        </div>
      <?php endforeach ?>
    </div>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
    <h2 class="text-sm font-semibold mb-3">New tag</h2>
    <form method="post" action="<?= e(admin_url('tags')) ?>" class="space-y-3 text-sm">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <input name="name" required placeholder="Tag name" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
      <input name="slug" placeholder="Optional slug" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5 font-mono">
      <button class="w-full px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Add tag</button>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
