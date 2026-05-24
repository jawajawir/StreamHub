<?php /** @var array $rows */
ob_start(); ?>
<header class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold tracking-tight">Watch history</h1>
  <div class="flex gap-2">
    <form method="post" action="/account/history/pause" class="inline">
      <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
      <input type="hidden" name="pause" value="1">
      <button class="text-xs text-zinc-300 hover:text-zinc-100 px-3 py-1.5 rounded border border-zinc-800 hover:bg-zinc-800/40">Pause</button>
    </form>
    <form method="post" action="/account/history/clear" class="inline" onsubmit="return confirm('Clear watch history?')">
      <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
      <button class="text-xs text-red-400 hover:text-red-300 px-3 py-1.5 rounded border border-red-900/40 hover:bg-red-950/30">Clear all</button>
    </form>
  </div>
</header>
<?php if (empty($rows)): ?>
  <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center text-sm text-zinc-400">No history yet.</div>
<?php else: ?>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php foreach ($rows as $r): ?>
      <a href="/watch/<?= e($r['slug']) ?>" class="group">
        <div class="relative aspect-video rounded-xl overflow-hidden bg-zinc-900 border border-zinc-800 group-hover:border-brand-600/60">
          <?php if (!empty($r['thumbnail_url'])): ?><img src="<?= e($r['thumbnail_url']) ?>" alt="" class="w-full h-full object-cover"><?php endif ?>
          <div class="absolute inset-x-0 bottom-0 h-1 bg-zinc-900/80"><div class="h-full bg-brand-500" style="width: <?= max(2, min(100, (int) $r['progress_percent'])) ?>%"></div></div>
        </div>
        <div class="mt-2 text-sm line-clamp-2 group-hover:text-brand-300"><?= e($r['title']) ?></div>
        <div class="text-[11px] text-zinc-500 mt-0.5"><?= e(date('M j, Y H:i', strtotime((string) $r['last_watched_at']))) ?></div>
      </a>
    <?php endforeach ?>
  </div>
<?php endif ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
