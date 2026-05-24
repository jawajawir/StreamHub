<?php /** @var string $heading @var ?string $sub @var ?array $actions Each: ['label','href','icon','primary'] */
$actions = $actions ?? [];
?>
<header class="mb-5 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold tracking-tight"><?= e($heading) ?></h1>
    <?php if (!empty($sub)): ?><p class="mt-0.5 text-sm text-zinc-400"><?= e($sub) ?></p><?php endif ?>
  </div>
  <?php if ($actions): ?>
    <div class="flex flex-wrap items-center gap-2">
      <?php foreach ($actions as $a): ?>
        <a href="<?= e($a['href']) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm <?= !empty($a['primary']) ? 'bg-brand-600 hover:bg-brand-500 text-white' : 'border border-zinc-800 hover:bg-zinc-800/40 text-zinc-200' ?>">
          <?php if (!empty($a['icon'])): ?><i data-lucide="<?= e($a['icon']) ?>" class="w-4 h-4"></i><?php endif ?>
          <?= e($a['label']) ?>
        </a>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</header>
