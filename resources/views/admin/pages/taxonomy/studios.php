<?php /** @var array $rows */
ob_start();
$heading = 'Studios / Makers'; $sub = count($rows) . ' studios';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="text-left text-xs text-zinc-500 bg-zinc-900/60"><tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Slug</th><th class="px-4 py-2">Count</th><th></th></tr></thead>
      <tbody class="divide-y divide-zinc-800/70">
        <?php if (empty($rows)): ?><tr><td colspan="4" class="text-center py-8 text-zinc-500">No studios yet.</td></tr><?php endif ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="px-4 py-2 font-medium"><?= e($r['name']) ?></td>
            <td class="px-4 py-2 font-mono text-xs text-zinc-400"><?= e($r['slug']) ?></td>
            <td class="px-4 py-2 tabular-nums"><?= (int) $r['content_count'] ?></td>
            <td class="px-4 py-2 text-right">
              <form method="post" action="<?= e(admin_url('studios/' . $r['id'] . '/delete')) ?>" onsubmit="return confirm('Delete studio?')" class="inline">
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
    <h2 class="text-sm font-semibold mb-3">New studio</h2>
    <form method="post" action="<?= e(admin_url('studios')) ?>" class="space-y-3 text-sm">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <input name="name" required placeholder="Name" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
      <input name="logo_url" placeholder="Logo URL" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
      <input name="website_url" placeholder="Website URL" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
      <textarea name="bio" rows="3" placeholder="Description" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5"></textarea>
      <button class="w-full px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Add studio</button>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
