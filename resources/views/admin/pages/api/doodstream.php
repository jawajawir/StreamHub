<?php /** @var ?array $cred @var array $logs */
ob_start();
$heading = 'API Manager · Doodstream';
$sub = 'Encrypted server-side credential. Never exposed to the frontend.';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">API key</h2>
      <?php if ($cred): ?>
        <div class="text-xs text-zinc-500">Stored masked value</div>
        <div class="font-mono text-lg"><?= e((string) $cred['masked_value']) ?></div>
        <div class="mt-3 text-xs text-zinc-500">Last test:
          <?php $tc = ($cred['last_test_status'] ?? 'unknown') === 'success' ? 'emerald' : (($cred['last_test_status'] ?? 'unknown') === 'failed' ? 'red' : 'zinc'); ?>
          <span class="text-<?= $tc ?>-300"><?= e($cred['last_test_status'] ?? 'unknown') ?></span>
          <?php if (!empty($cred['last_tested_at'])): ?>· <?= e($cred['last_tested_at']) ?><?php endif ?>
        </div>
        <?php if (!empty($cred['last_test_message'])): ?>
          <div class="mt-1 text-xs text-zinc-400 break-all"><?= e($cred['last_test_message']) ?></div>
        <?php endif ?>
      <?php else: ?>
        <div class="text-sm text-zinc-400">No API key configured yet.</div>
      <?php endif ?>
      <form method="post" action="<?= e(admin_url('api/doodstream/key')) ?>" class="mt-4 grid sm:grid-cols-[1fr_auto] gap-2 text-sm" x-data="{show:false}">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="relative">
          <input :type="show?'text':'password'" name="api_key" required placeholder="Paste new API key" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 pr-10 text-sm font-mono">
          <button type="button" @click="show=!show" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500"><i data-lucide="eye" class="w-4 h-4" x-show="!show"></i><i data-lucide="eye-off" class="w-4 h-4" x-show="show" x-cloak></i></button>
        </div>
        <button class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Save key</button>
      </form>
      <form method="post" action="<?= e(admin_url('api/doodstream/test')) ?>" class="mt-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button class="px-3 py-1.5 rounded-lg border border-zinc-800 hover:bg-zinc-800/40 text-xs">Test connection</button>
      </form>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">Recent sync logs</h2>
      <?php if (empty($logs)): ?>
        <div class="text-sm text-zinc-500">No sync runs yet.</div>
      <?php else: ?>
        <table class="w-full text-sm">
          <thead class="text-xs text-zinc-500"><tr><th class="text-left py-2">Started</th><th class="text-left">Type</th><th class="text-left">Status</th><th class="text-left">Created</th><th class="text-left">Updated</th><th class="text-left">Failed</th></tr></thead>
          <tbody class="divide-y divide-zinc-800/70">
            <?php foreach ($logs as $l): $sc = match ($l['status']) { 'success' => 'emerald', 'failed','partial_failed' => 'red', 'running' => 'amber', default => 'zinc' }; ?>
              <tr class="text-xs">
                <td class="py-2"><?= e($l['created_at']) ?></td>
                <td><?= e($l['sync_type']) ?></td>
                <td><span class="px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300"><?= e($l['status']) ?></span></td>
                <td><?= (int) $l['created_files'] ?></td>
                <td><?= (int) $l['updated_files'] ?></td>
                <td><?= (int) $l['failed_files'] ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php endif ?>
    </div>
  </div>
  <div class="rounded-2xl border border-amber-900/40 bg-amber-950/20 p-5">
    <h3 class="text-sm font-semibold text-amber-200 mb-2">Notes</h3>
    <ul class="text-xs text-amber-200/80 space-y-1.5 list-disc pl-4">
      <li>The API key is encrypted with the project's <code>ENCRYPTION_KEY</code>.</li>
      <li>Only the masked value is shown; the raw key is never re-rendered.</li>
      <li>All write actions on this page are CSRF-protected and audit-logged.</li>
      <li>Auto sync via cron will appear in the logs once enabled.</li>
    </ul>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
