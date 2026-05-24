<?php /** @var array $stats @var array $continue @var array $prefs */
ob_start(); ?>
<header class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight">Welcome, <?= e($auth_user['display_name'] ?: $auth_user['username']) ?></h1>
  <div class="mt-1 text-sm text-zinc-400 flex items-center gap-2">
    <span class="px-2 py-0.5 rounded bg-zinc-800 text-zinc-300 text-xs"><?= e(strtoupper($auth_user['membership_tier'])) ?></span>
    <?php if (empty($auth_user['email_verified_at'])): ?>
      <span class="text-amber-400 text-xs">· Email not verified</span>
    <?php endif ?>
  </div>
</header>

<div class="grid sm:grid-cols-3 gap-4 mb-8">
  <a href="/account/favorites" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4 hover:border-brand-600/40">
    <div class="text-xs text-zinc-500">Favorites</div><div class="mt-1 text-2xl font-semibold"><?= number_format($stats['favorites']) ?></div>
  </a>
  <a href="/account/history" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4 hover:border-brand-600/40">
    <div class="text-xs text-zinc-500">Watch history</div><div class="mt-1 text-2xl font-semibold"><?= number_format($stats['history']) ?></div>
  </a>
  <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4">
    <div class="text-xs text-zinc-500">Liked</div><div class="mt-1 text-2xl font-semibold"><?= number_format($stats['liked']) ?></div>
  </div>
</div>

<?php if (!empty($continue)): ?>
<section class="mb-8">
  <h2 class="text-lg font-semibold tracking-tight mb-3">Continue watching</h2>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php foreach ($continue as $item): ?>
      <a href="/watch/<?= e($item['slug']) ?>" class="group">
        <div class="relative aspect-video rounded-xl overflow-hidden bg-zinc-900 border border-zinc-800 group-hover:border-brand-600/60">
          <?php if (!empty($item['thumbnail_url'])): ?><img src="<?= e($item['thumbnail_url']) ?>" alt="" class="w-full h-full object-cover"><?php endif ?>
          <div class="absolute inset-x-0 bottom-0 h-1 bg-zinc-900/80"><div class="h-full bg-brand-500" style="width: <?= max(2, min(100, (int) $item['progress_percent'])) ?>%"></div></div>
        </div>
        <div class="mt-2 text-sm line-clamp-2 group-hover:text-brand-300"><?= e($item['title']) ?></div>
      </a>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<section class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5">
  <h2 class="text-lg font-semibold tracking-tight mb-4">Privacy &amp; preferences</h2>
  <form method="post" action="/account/privacy" class="grid sm:grid-cols-2 gap-4 text-sm">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="private_mode" value="1" <?= !empty($prefs['private_mode']) ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500"> Private mode</label>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="email_notifications" value="1" <?= !empty($prefs['email_notifications']) ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500"> Receive email notifications</label>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="newsletter_opt_in" value="1" <?= !empty($prefs['newsletter_opt_in']) ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500"> Newsletter opt-in</label>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Favorites visibility</label>
      <select name="show_favorites" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5 text-sm">
        <?php foreach (['private','members','public'] as $v): ?>
          <option value="<?= $v ?>" <?= ($prefs['show_favorites'] ?? 'private') === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="sm:col-span-2 flex flex-wrap items-center gap-2 mt-2">
      <button class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Save</button>
    </div>
  </form>
  <hr class="my-5 border-zinc-800">
  <form method="post" action="/account/logout-devices">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <button class="text-xs text-red-400 hover:text-red-300 inline-flex items-center gap-1.5"><i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign out of all devices</button>
  </form>
</section>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
