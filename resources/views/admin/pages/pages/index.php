<?php /** @var array $rows */
ob_start();
$heading = 'Pages';
$sub = 'Edit static, legal and trust pages.';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="text-xs text-zinc-500 bg-zinc-900/60"><tr><th class="text-left px-4 py-2">Title</th><th class="text-left px-4 py-2">Slug</th><th class="text-left px-4 py-2">Type</th><th class="text-left px-4 py-2">Status</th><th></th></tr></thead>
    <tbody class="divide-y divide-zinc-800/70">
      <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center py-10 text-zinc-500">No pages.</td></tr><?php endif ?>
      <?php foreach ($rows as $r): $sc = match ($r['status']) { 'published' => 'emerald', 'archived' => 'zinc', 'draft' => 'amber', default => 'zinc' }; ?>
        <tr>
          <td class="px-4 py-2 font-medium"><?= e($r['title']) ?></td>
          <td class="px-4 py-2 font-mono text-xs text-zinc-400"><?= e($r['slug']) ?></td>
          <td class="px-4 py-2 text-xs"><?= e($r['page_type']) ?></td>
          <td class="px-4 py-2"><span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 border border-<?= $sc ?>-500/30"><?= e($r['status']) ?></span></td>
          <td class="px-4 py-2 text-right"><a href="<?= e(admin_url('pages/' . $r['id'] . '/edit')) ?>" class="text-xs text-brand-400 hover:text-brand-300">Edit</a></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
