<?php /** @var array $rows @var array $studios */
ob_start();
$heading = 'Series / Collections'; $sub = count($rows) . ' series';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="text-left text-xs text-zinc-500 bg-zinc-900/60"><tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Studio</th><th class="px-4 py-2">Count</th><th></th></tr></thead>
      <tbody class="divide-y divide-zinc-800/70">
        <?php if (empty($rows)): ?><tr><td colspan="4" class="text-center py-8 text-zinc-500">No series yet.</td></tr><?php endif ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="px-4 py-2 font-medium"><?= e($r['name']) ?></td>
            <td class="px-4 py-2 text-xs"><?= e($r['studio_name'] ?? '—') ?></td>
            <td class="px-4 py-2 tabular-nums"><?= (int) $r['content_count'] ?></td>
            <td class="px-4 py-2 text-right">
              <form method="post" action="<?= e(admin_url('series/' . $r['id'] . '/delete')) ?>" onsubmit="return confirm('Delete series?')" class="inline">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button class="text-xs text-red-400 hover:text-red-300">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
    <h2 class="text-sm font-semibold mb-3">New series</h2>
    <form method="post" action="<?= e(admin_url('series')) ?>" class="space-y-3 text-sm">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <input name="name" required placeholder="Series name" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
      <select name="studio_id" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
        <option value="">No studio</option>
        <?php foreach ($studios as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach ?>
      </select>
      <input name="cover_url" placeholder="Cover image URL" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
      <textarea name="description" rows="3" placeholder="Description" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5"></textarea>
      <button class="w-full px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Add series</button>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
