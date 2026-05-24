<?php /** @var array $user @var array $memberships @var array $sessions @var array $audit @var array $plans */
ob_start();
$heading = $user['username'];
$sub = $user['email'];
$actions = [['label' => 'Back', 'href' => admin_url('users'), 'icon' => 'arrow-left']];
include __DIR__ . '/../../components/page_header.php';
?>

<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">Update membership</h2>
      <form method="post" action="<?= e(admin_url('users/' . $user['id'] . '/membership')) ?>" class="grid sm:grid-cols-3 gap-3 text-sm">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Plan</label>
          <select name="plan" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
            <?php foreach ($plans as $p): ?>
              <option value="<?= e($p['code']) ?>" <?= $user['membership_tier'] === $p['code'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Expires at</label>
          <input type="datetime-local" name="expires_at" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
        </div>
        <div class="flex items-end">
          <button class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Apply</button>
        </div>
        <div class="sm:col-span-3">
          <label class="block text-xs text-zinc-500 mb-1">Note</label>
          <input name="note" placeholder="Reason / payment ref" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
        </div>
      </form>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">Account status</h2>
      <form method="post" action="<?= e(admin_url('users/' . $user['id'] . '/status')) ?>" class="flex flex-wrap items-center gap-2 text-sm">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <select name="status" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
          <?php foreach (['active','suspended','banned'] as $s): ?><option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach ?>
        </select>
        <button class="px-3 py-1.5 rounded-lg border border-zinc-800 hover:bg-zinc-800/40 text-zinc-200">Update status</button>
      </form>
      <hr class="my-4 border-zinc-800">
      <form method="post" action="<?= e(admin_url('users/' . $user['id'] . '/ban')) ?>" onsubmit="return confirm('Ban this user permanently?')" class="flex flex-wrap items-center gap-2 text-sm">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input name="reason" placeholder="Ban reason" class="flex-1 min-w-[200px] rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
        <button class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white">Ban user</button>
      </form>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">Membership history</h2>
      <?php if (empty($memberships)): ?>
        <div class="text-xs text-zinc-500 py-4">No membership records.</div>
      <?php else: ?>
        <table class="w-full text-sm"><thead class="text-xs text-zinc-500"><tr><th class="text-left py-2">Plan</th><th class="text-left">Status</th><th class="text-left">Starts</th><th class="text-left">Expires</th></tr></thead><tbody class="divide-y divide-zinc-800/70">
        <?php foreach ($memberships as $m): ?>
          <tr><td class="py-2"><?= e($m['plan_name']) ?></td><td><?= e($m['status']) ?></td><td><?= e((string) ($m['starts_at'] ?? '—')) ?></td><td><?= e((string) ($m['expires_at'] ?? '—')) ?></td></tr>
        <?php endforeach ?>
        </tbody></table>
      <?php endif ?>
    </div>
  </div>
  <div class="space-y-4">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">Profile</h2>
      <dl class="text-sm space-y-1.5">
        <div class="flex justify-between"><dt class="text-zinc-500">ID</dt><dd>#<?= (int) $user['id'] ?></dd></div>
        <div class="flex justify-between"><dt class="text-zinc-500">Tier</dt><dd><?= e($user['membership_tier']) ?></dd></div>
        <div class="flex justify-between"><dt class="text-zinc-500">Status</dt><dd><?= e($user['status']) ?></dd></div>
        <div class="flex justify-between"><dt class="text-zinc-500">Verified</dt><dd><?= e($user['email_verified_at'] ?? '—') ?></dd></div>
        <div class="flex justify-between"><dt class="text-zinc-500">Created</dt><dd><?= e($user['created_at']) ?></dd></div>
      </dl>
    </div>
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
      <h2 class="text-sm font-semibold mb-3">Recent audit</h2>
      <ul class="text-xs space-y-2">
        <?php if (empty($audit)): ?><li class="text-zinc-500">No entries.</li><?php endif ?>
        <?php foreach ($audit as $a): ?>
          <li><span class="font-mono text-zinc-300"><?= e($a['action']) ?></span><span class="text-zinc-500"> · <?= e((string) $a['created_at']) ?></span></li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
