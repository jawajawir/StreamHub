<?php /** @var array $sections */
ob_start(); ?>
<?php if (empty($sections)): ?>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-10 text-center">
    <div class="mx-auto w-12 h-12 rounded-full bg-zinc-800 flex items-center justify-center"><i data-lucide="film" class="w-5 h-5 text-zinc-400"></i></div>
    <h2 class="mt-4 text-lg font-semibold">No content published yet</h2>
    <p class="mt-1 text-sm text-zinc-400">Sign in to the admin panel to add content.</p>
  </div>
<?php else: ?>
  <?php foreach ($sections as $sec): $section = $sec['section']; $items = $sec['items']; ?>
    <section class="mb-10">
      <div class="flex items-end justify-between mb-3">
        <h2 class="text-lg font-semibold tracking-tight"><?= e($section['title']) ?></h2>
      </div>
      <?php if ($section['section_type'] === 'tag'): ?>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($items as $t): ?>
            <a href="/tag/<?= e($t['slug']) ?>" class="px-3 py-1.5 rounded-full border border-zinc-800 bg-zinc-900/50 text-sm hover:border-brand-500/40 hover:text-brand-300">
              #<?= e($t['name']) ?> <span class="text-[11px] text-zinc-500">· <?= number_format((int) $t['content_count']) ?></span>
            </a>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <?php include __DIR__ . '/../components/grid.php' ?>
      <?php endif ?>
    </section>
  <?php endforeach ?>
<?php endif ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
