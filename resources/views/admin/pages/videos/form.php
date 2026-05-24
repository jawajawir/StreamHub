<?php /** @var ?array $row @var ?array $source @var array $cats @var array $tags @var array $performers
                @var array $studios @var array $series @var array $selectedCats @var array $selectedTags
                @var array $selectedPerformers @var array $iframeAllowlist */
$errors = \App\Core\Session::instance()->pull('_errors', []);
$old    = \App\Core\Session::instance()->pull('_old_input', []);
$isEdit = (bool) $row;
$action = $isEdit ? admin_url('videos/' . $row['id']) : admin_url('videos');
ob_start();
$heading = $isEdit ? 'Edit content' : 'Add content';
$sub = $isEdit ? '#' . $row['id'] . ' · ' . $row['title'] : 'Direct MySQL or Doodstream source';
$actions = [['label' => 'Back', 'href' => admin_url('videos'), 'icon' => 'arrow-left']];
include __DIR__ . '/../../components/page_header.php';
?>
<form method="post" action="<?= e($action) ?>" class="grid lg:grid-cols-3 gap-4" x-data="{ source: '<?= e($old['source_type'] ?? $row['source_type'] ?? 'direct_mysql') ?>' }">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <div class="lg:col-span-2 space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 space-y-4">
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Title <span class="text-red-500">*</span></label>
        <input name="title" required value="<?= e($old['title'] ?? $row['title'] ?? '') ?>" class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['title']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Slug (auto from title if empty)</label>
          <input name="slug" value="<?= e($old['slug'] ?? $row['slug'] ?? '') ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm font-mono">
        </div>
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Internal code (optional)</label>
          <input name="internal_code" value="<?= e($old['internal_code'] ?? $row['internal_code'] ?? '') ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm font-mono">
        </div>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Description</label>
        <textarea name="description" rows="5" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm"><?= e($old['description'] ?? $row['description'] ?? '') ?></textarea>
      </div>
      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Release date</label>
          <input type="date" name="release_date" value="<?= e($old['release_date'] ?? $row['release_date'] ?? '') ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Runtime (seconds)</label>
          <input type="number" min="0" name="runtime_seconds" value="<?= e((string) ($old['runtime_seconds'] ?? $row['runtime_seconds'] ?? '')) ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Thumbnail URL</label>
          <input name="thumbnail_url" value="<?= e($old['thumbnail_url'] ?? $row['thumbnail_url'] ?? '') ?>" placeholder="https://…" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h3 class="text-sm font-semibold mb-3">Source</h3>
      <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
        <label class="cursor-pointer rounded-lg border px-3 py-2" :class="source === 'direct_mysql' ? 'border-brand-600 bg-brand-600/10 text-brand-200' : 'border-zinc-800 text-zinc-300'">
          <input type="radio" name="source_type" value="direct_mysql" class="hidden" x-model="source"> Direct MySQL
          <div class="text-[11px] text-zinc-500 mt-0.5">Video.js + HLS.js</div>
        </label>
        <label class="cursor-pointer rounded-lg border px-3 py-2" :class="source === 'doodstream' ? 'border-brand-600 bg-brand-600/10 text-brand-200' : 'border-zinc-800 text-zinc-300'">
          <input type="radio" name="source_type" value="doodstream" class="hidden" x-model="source"> Doodstream
          <div class="text-[11px] text-zinc-500 mt-0.5">Native iframe</div>
        </label>
      </div>

      <div x-show="source === 'doodstream'">
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-zinc-500 mb-1">Doodstream file_code</label>
            <input name="doodstream_file_code" value="<?= e($old['doodstream_file_code'] ?? $source['doodstream_file_code'] ?? '') ?>" placeholder="abc123xyz" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm font-mono">
          </div>
          <div>
            <label class="block text-xs text-zinc-500 mb-1">Or full iframe URL</label>
            <input name="iframe_url" value="<?= e($old['iframe_url'] ?? $source['iframe_url'] ?? '') ?>" placeholder="https://dood.li/e/…" class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['iframe_url']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
            <?php if (!empty($errors['iframe_url'])): ?><p class="text-xs text-red-400 mt-1"><?= e($errors['iframe_url']) ?></p><?php endif ?>
          </div>
        </div>
        <details class="mt-3 text-xs text-zinc-500">
          <summary class="cursor-pointer">View allowed embed domains (<?= count($iframeAllowlist) ?>)</summary>
          <div class="mt-2 font-mono break-all"><?= e(implode(', ', array_slice($iframeAllowlist, 0, 50))) ?></div>
        </details>
      </div>

      <div x-show="source === 'direct_mysql'" x-cloak>
        <label class="block text-xs text-zinc-500 mb-1">Direct media URL (MP4 or .m3u8)</label>
        <input name="direct_url" value="<?= e($old['direct_url'] ?? '') ?>" placeholder="https://cdn.example.com/file.mp4" class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['direct_url']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
        <p class="text-xs text-zinc-500 mt-1">Stored as a media_assets row and served via a signed playback URL.</p>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 space-y-3">
      <h3 class="text-sm font-semibold">Publishing</h3>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Lifecycle status</label>
        <select name="lifecycle_status" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <?php foreach (['draft','pending_review','published','scheduled','rejected','disabled','broken','archived'] as $s): $cur = $old['lifecycle_status'] ?? $row['lifecycle_status'] ?? 'draft'; ?>
            <option value="<?= $s ?>" <?= $cur === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Access level</label>
        <select name="access_level" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <?php foreach (['public','registered','premium','vip'] as $s): $cur = $old['access_level'] ?? $row['access_level'] ?? 'public'; ?>
            <option value="<?= $s ?>" <?= $cur === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Visibility</label>
        <select name="visibility_level" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <?php foreach (['visible','hidden','unlisted'] as $s): $cur = $old['visibility_level'] ?? $row['visibility_level'] ?? 'visible'; ?>
            <option value="<?= $s ?>" <?= $cur === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Scheduled publish at</label>
        <input type="datetime-local" name="scheduled_at" value="<?= e($old['scheduled_at'] ?? ($row['scheduled_at'] ? str_replace(' ', 'T', substr((string) $row['scheduled_at'], 0, 16)) : '')) ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
      </div>
      <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" <?= !empty($row['is_featured']) ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500"> Featured on homepage</label>
      <label class="inline-flex items-center gap-2 text-sm ml-4"><input type="checkbox" name="allow_comments" value="1" <?= ($row === null || !empty($row['allow_comments'])) ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500"> Allow comments</label>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 space-y-3">
      <h3 class="text-sm font-semibold">Taxonomy</h3>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Studio</label>
        <select name="studio_id" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <option value="0">—</option>
          <?php foreach ($studios as $s): ?><option value="<?= (int) $s['id'] ?>" <?= ($row['studio_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Series</label>
        <select name="series_id" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <option value="0">—</option>
          <?php foreach ($series as $s): ?><option value="<?= (int) $s['id'] ?>" <?= ($row['series_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Categories</label>
        <select name="categories[]" multiple size="6" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $selectedCats, true) ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Tags</label>
        <select name="tags[]" multiple size="6" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <?php foreach ($tags as $t): ?><option value="<?= (int) $t['id'] ?>" <?= in_array((int) $t['id'], $selectedTags, true) ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-zinc-500 mb-1">Performers</label>
        <select name="performers[]" multiple size="6" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
          <?php foreach ($performers as $p): ?><option value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $selectedPerformers, true) ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <button class="w-full px-4 py-2.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Save content</button>
      <a href="<?= e(admin_url('videos')) ?>" class="text-center text-xs text-zinc-400 hover:text-zinc-200">Cancel</a>
      <?php if ($isEdit): ?>
        <hr class="border-zinc-800 my-2">
        <form method="post" action="<?= e(admin_url('videos/' . $row['id'] . '/delete')) ?>" onsubmit="return confirm('Archive this content?')">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="archive">
          <button class="w-full px-3 py-1.5 rounded-lg border border-zinc-800 hover:bg-zinc-800/40 text-xs text-zinc-300">Archive</button>
        </form>
      <?php endif ?>
    </div>
  </div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
