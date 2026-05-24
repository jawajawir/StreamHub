<?php /** @var array $page */
ob_start(); ?>
<article class="prose prose-invert max-w-3xl mx-auto prose-headings:tracking-tight prose-a:text-brand-400">
  <h1><?= e($page['title']) ?></h1>
  <?= (string) ($page['sanitized_body'] ?? $page['body'] ?? '') ?>
  <p class="not-prose mt-8 text-xs text-zinc-500">Last updated <?= e(date('F j, Y', strtotime((string) $page['updated_at']))) ?></p>
</article>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
