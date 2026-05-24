<?php /** @var array $row */
ob_start();
$heading = 'Edit page';
$sub = $row['title'];
$actions = [
    ['label' => 'View', 'href' => '/page/' . $row['slug'], 'icon' => 'external-link'],
    ['label' => 'Back', 'href' => admin_url('pages'), 'icon' => 'arrow-left'],
];
include __DIR__ . '/../../components/page_header.php';
?>
<form method="post" action="<?= e(admin_url('pages/' . $row['id'])) ?>" class="grid lg:grid-cols-3 gap-4">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div class="lg:col-span-2 space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 space-y-3">
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Title</label>
        <input name="title" required value="<?= e($row['title']) ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Slug</label>
        <input name="slug" value="<?= e($row['slug']) ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm font-mono">
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Body (HTML allowed - sanitized on save)</label>
        <textarea name="body" rows="20" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm font-mono"><?= e($row['body'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <div class="space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h3 class="text-sm font-semibold mb-3">Status</h3>
      <div class="text-sm">Type: <strong><?= e($row['page_type']) ?></strong></div>
      <div class="mt-2 text-sm">Status: <strong><?= e($row['status']) ?></strong></div>
      <button class="mt-4 w-full px-3 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm">Save</button>
    </div>
    <form method="post" action="<?= e(admin_url('pages/' . $row['id'] . '/publish')) ?>">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <button class="w-full px-3 py-2 rounded-lg border border-zinc-800 hover:bg-zinc-800/40 text-sm"><?= $row['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
    </form>
  </div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
