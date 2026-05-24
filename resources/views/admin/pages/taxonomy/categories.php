<?php /** @var array $rows */
ob_start();
$heading = 'Categories'; $sub = count($rows) . ' total';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="text-left text-xs text-zinc-500 bg-zinc-900/60"><tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Slug</th><th class="px-4 py-2">Count</th><th class="px-4 py-2">Status</th><th></th></tr></thead>
      <tbody class="divide-y divide-zinc-800/70">
        <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center py-8 text-zinc-500">No categories yet.</td></tr><?php endif ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="px-4 py-2 font-medium"><?= e($r['name']) ?></td>
            <td class="px-4 py-2 font-mono text-xs text-zinc-400"><?= e($r['slug']) ?></td>
            <td class="px-4 py-2 tabular-nums"><?= (int) $r['content_count'] ?></td>
            <td class="px-4 py-2 text-xs"><?= e($r['status']) ?></td>
            <td class="px-4 py-2 text-right">
              <details>
                <summary class="cursor-pointer text-xs text-zinc-300 inline-flex items-center gap-1"><i data-lucide="more-horizontal" class="w-3 h-3"></i></summary>
                <form method="post" action="<?= e(admin_url('categories/' . $r['id'])) ?>" class="mt-2 grid gap-2 text-xs bg-zinc-950 border border-zinc-800 p-2 rounded">
                  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                  <input name="name" value="<?= e($r['name']) ?>" class="rounded bg-zinc-950 border border-zinc-800 px-2 py-1">
                  <input name="slug" value="<?= e($r['slug']) ?>" class="rounded bg-zinc-950 border border-zinc-800 px-2 py-1 font-mono">
                  <textarea name="description" class="rounded bg-zinc-950 border border-zinc-800 px-2 py-1"><?= e($r['description'] ?? '') ?></textarea>
                  <select name="status" class="rounded bg-zinc-950 border border-zinc-800 px-2 py-1">
                    <?php foreach (['active','hidden','disabled'] as $s): ?><option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach ?>
                  </select>
                  <div class="flex gap-2">
                    <button class="px-2 py-1 rounded bg-brand-600 hover:bg-brand-500 text-white">Save</button>
                  </div>
                </form>
                <form method="post" action="<?= e(admin_url('categories/' . $r['id'] . '/delete')) ?>" onsubmit="return confirm('Delete?')" class="mt-2">
                  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                  <button class="text-xs text-red-400 hover:text-red-300">Delete</button>
                </form>
              </details>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
    <h2 class="text-sm font-semibold mb-3">New category</h2>
    <form method="post" action="<?= e(admin_url('categories')) ?>" class="space-y-3 text-sm">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <div><label class="block text-xs text-zinc-500 mb-1">Name</label><input name="name" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5"></div>
      <div><label class="block text-xs text-zinc-500 mb-1">Slug (auto)</label><input name="slug" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5 font-mono"></div>
      <div><label class="block text-xs text-zinc-500 mb-1">Description</label><textarea name="description" rows="3" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5"></textarea></div>
      <button class="w-full px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Add category</button>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
