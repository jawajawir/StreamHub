<?php /** @var array $features @var array $site @var array $age_gate @var array $player @var array $iframe @var array $seo */
ob_start();
$heading = 'Settings';
$sub = 'Real-time saved to the settings table.';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="grid lg:grid-cols-2 gap-4">
  <form method="post" action="<?= e(admin_url('settings')) ?>" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 space-y-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <h2 class="text-sm font-semibold">Site</h2>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Site name</label>
      <input name="site_name" value="<?= e((string) $site['name']) ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Tagline</label>
      <input name="site_tagline" value="<?= e((string) $site['tagline']) ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Default meta description</label>
      <textarea name="site_description" rows="3" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm"><?= e((string) $site['description']) ?></textarea>
    </div>

    <h2 class="text-sm font-semibold pt-3 border-t border-zinc-800">Age gate</h2>
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="age_gate_enabled" value="1" <?= !empty($age_gate['enabled']) ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500"> Enabled</label>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Cookie / record expiry (days)</label>
      <input type="number" min="1" max="365" name="age_gate_expiry_days" value="<?= (int) $age_gate['expiry_days'] ?>" class="w-32 rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>

    <h2 class="text-sm font-semibold pt-3 border-t border-zinc-800">Player</h2>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Guest countdown before play (seconds)</label>
      <input type="number" min="0" max="30" name="player_guest_countdown" value="<?= (int) $player['guest_countdown'] ?>" class="w-32 rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>

    <h2 class="text-sm font-semibold pt-3 border-t border-zinc-800">Security</h2>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Iframe allowlist (extra domains, comma separated)</label>
      <textarea name="iframe_allowlist_extra" rows="3" placeholder="example.com, dood.example" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm font-mono"><?= e((string) $iframe['allowlist_extra']) ?></textarea>
      <p class="mt-1 text-xs text-zinc-500">Default Doodstream domains are always allowed.</p>
    </div>

    <h2 class="text-sm font-semibold pt-3 border-t border-zinc-800">SEO</h2>
    <div>
      <label class="block text-xs text-zinc-500 mb-1">Default title suffix</label>
      <input name="seo_default_title_suffix" value="<?= e((string) $seo['default_title_suffix']) ?>" placeholder="| StreamHub" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>

    <div class="pt-3 border-t border-zinc-800">
      <button class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Save settings</button>
    </div>
  </form>

  <form method="post" action="<?= e(admin_url('settings/features')) ?>" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 space-y-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <h2 class="text-sm font-semibold">Feature toggles</h2>
    <p class="text-xs text-zinc-500">Disabled features are hidden from public navigation and reject direct POSTs.</p>
    <div class="grid sm:grid-cols-2 gap-2">
      <?php foreach ($features as $f): ?>
        <label class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-zinc-800 hover:bg-zinc-800/40">
          <div>
            <div class="text-sm font-medium"><?= e($f['feature_name']) ?></div>
            <div class="text-[10px] uppercase tracking-wider text-zinc-500"><?= e($f['feature_group']) ?></div>
          </div>
          <input type="checkbox" name="features[]" value="<?= e($f['feature_key']) ?>" <?= (int) $f['is_enabled'] === 1 ? 'checked' : '' ?> class="rounded border-zinc-700 bg-zinc-950 text-brand-500">
        </label>
      <?php endforeach ?>
    </div>
    <button class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Save toggles</button>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
