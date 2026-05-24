<?php /** @var array $rows @var int $page @var int $pages @var int $total
                @var string $q @var string $source @var string $status @var string $access */
ob_start();
$heading = 'Video Manager'; $sub = number_format($total) . ' total';
$actions = [['label' => 'Add content', 'href' => admin_url('videos/create'), 'icon' => 'plus', 'primary' => true]];
include __DIR__ . '/../../components/page_header.php';
?>
<form method="get" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-3 mb-4 flex flex-wrap gap-2 text-sm">
  <input name="q" value="<?= e($q) ?>" placeholder="Search title / slug / code" class="flex-1 min-w-[200px] rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
  <select name="source" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
    <option value="">Any source</option>
    <option value="doodstream"  <?= $source === 'doodstream' ? 'selected' : '' ?>>Doodstream</option>
    <option value="direct_mysql"<?= $source === 'direct_mysql' ? 'selected' : '' ?>>Direct MySQL</option>
  </select>
  <select name="status" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
    <option value="">Any status</option>
    <?php foreach (['draft','pending_review','published','scheduled','rejected','disabled','broken','archived'] as $s): ?>
      <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
    <?php endforeach ?>
  </select>
  <select name="access" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
    <option value="">Any access</option>
    <?php foreach (['public','registered','premium','vip'] as $a): ?>
      <option value="<?= $a ?>" <?= $access === $a ? 'selected' : '' ?>><?= e($a) ?></option>
    <?php endforeach ?>
  </select>
  <button class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Filter</button>
</form>

<div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="text-left text-xs text-zinc-500 bg-zinc-900/60"><tr>
      <th class="px-4 py-2.5">Title</th><th class="px-4 py-2.5">Source</th>
      <th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5">Access</th>
      <th class="px-4 py-2.5">Views</th><th class="px-4 py-2.5">Created</th><th></th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-800/70">
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center py-12 text-zinc-500">
          No content yet. <a href="<?= e(admin_url('videos/create')) ?>" class="text-brand-400 hover:underline">Add your first video</a>.
        </td></tr>
      <?php endif ?>
      <?php foreach ($rows as $r):
        $sc = match ($r['lifecycle_status']) { 'published' => 'emerald', 'broken' => 'red', 'disabled' => 'red', 'scheduled' => 'amber', 'archived' => 'zinc', default => 'zinc' };
        $tc = match ($r['access_level']) { 'vip' => 'fuchsia', 'premium' => 'amber', 'registered' => 'blue', default => 'zinc' };
      ?>
        <tr class="hover:bg-zinc-800/30">
          <td class="px-4 py-2 max-w-[420px]">
            <div class="flex items-center gap-3">
              <div class="w-14 aspect-video rounded bg-zinc-800 overflow-hidden shrink-0">
                <?php if (!empty($r['thumbnail_url'])): ?><img src="<?= e($r['thumbnail_url']) ?>" alt="" class="w-full h-full object-cover"><?php endif ?>
              </div>
              <div class="min-w-0">
                <div class="font-medium line-clamp-1"><?= e($r['title']) ?></div>
                <div class="text-[11px] text-zinc-500 line-clamp-1">/<?= e($r['slug']) ?></div>
              </div>
            </div>
          </td>
          <td class="px-4 py-2 text-xs"><?= e(str_replace('_',' ',$r['source_type'])) ?></td>
          <td class="px-4 py-2"><span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 border border-<?= $sc ?>-500/30"><?= e($r['lifecycle_status']) ?></span></td>
          <td class="px-4 py-2"><span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $tc ?>-500/15 text-<?= $tc ?>-300 border border-<?= $tc ?>-500/30"><?= e($r['access_level']) ?></span></td>
          <td class="px-4 py-2 tabular-nums"><?= number_format((int) $r['view_count']) ?></td>
          <td class="px-4 py-2 text-xs text-zinc-400"><?= e(date('M j, Y', strtotime((string) $r['created_at']))) ?></td>
          <td class="px-4 py-2 text-right">
            <a href="<?= e(admin_url('videos/' . $r['id'] . '/edit')) ?>" class="text-xs text-brand-400 hover:text-brand-300 inline-flex items-center gap-1">Edit <i data-lucide="edit-3" class="w-3 h-3"></i></a>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php
$baseUrl = admin_url('videos') . '?q=' . rawurlencode($q) . '&source=' . rawurlencode($source) . '&status=' . rawurlencode($status) . '&access=' . rawurlencode($access);
include __DIR__ . '/../../components/pagination.php';
$content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
