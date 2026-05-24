<?php /** @var array $item — id, slug, title, thumbnail_url, runtime_seconds, view_count?, access_level? */
$thumb = $item['thumbnail_url'] ?? '';
$runtime = (int) ($item['runtime_seconds'] ?? 0);
$views   = (int) ($item['view_count'] ?? 0);
$access  = $item['access_level'] ?? 'public';

$mins = $runtime > 0 ? gmdate($runtime >= 3600 ? 'G:i:s' : 'i:s', $runtime) : null;
$badge = match ($access) { 'premium' => ['Premium', 'bg-amber-500/15 text-amber-300 border-amber-500/30'], 'vip' => ['VIP','bg-fuchsia-500/15 text-fuchsia-300 border-fuchsia-500/30'], 'registered' => ['Members','bg-blue-500/15 text-blue-300 border-blue-500/30'], default => null };
?>
<a href="/watch/<?= e($item['slug']) ?>" class="group block">
  <div class="relative aspect-video rounded-xl overflow-hidden bg-zinc-900 border border-zinc-800/70 group-hover:border-brand-600/60 transition">
    <?php if ($thumb): ?>
      <img src="<?= e($thumb) ?>" alt="" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
    <?php else: ?>
      <div class="w-full h-full flex items-center justify-center text-zinc-600"><i data-lucide="image" class="w-8 h-8"></i></div>
    <?php endif ?>
    <?php if ($mins): ?>
      <span class="absolute bottom-2 right-2 text-[11px] px-1.5 py-0.5 rounded bg-black/70 text-white tabular-nums"><?= e($mins) ?></span>
    <?php endif ?>
    <?php if ($badge): ?>
      <span class="absolute top-2 left-2 text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded border <?= $badge[1] ?>"><?= e($badge[0]) ?></span>
    <?php endif ?>
  </div>
  <div class="mt-2">
    <div class="text-sm font-medium line-clamp-2 group-hover:text-brand-300 transition"><?= e($item['title']) ?></div>
    <?php if ($views > 0): ?>
      <div class="mt-0.5 text-[11px] text-zinc-500"><?= number_format($views) ?> views</div>
    <?php endif ?>
  </div>
</a>
