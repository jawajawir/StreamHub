<?php /** @var array $row */
ob_start();
$heading = 'Membership request';
$sub = '#' . $row['id'] . ' · ' . $row['plan_name'];
$actions = [['label' => 'Back', 'href' => admin_url('membership-requests'), 'icon' => 'arrow-left']];
include __DIR__ . '/../../components/page_header.php';
$sc = match ($row['status']) { 'approved' => 'emerald', 'rejected' => 'red', 'reviewing' => 'amber', default => 'zinc' };
?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
    <div class="flex items-center gap-2 mb-3">
      <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 border border-<?= $sc ?>-500/30"><?= e($row['status']) ?></span>
      <span class="text-xs text-zinc-500"><?= e((string) $row['created_at']) ?></span>
    </div>
    <dl class="text-sm divide-y divide-zinc-800">
      <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">Plan</dt><dd class="col-span-2"><?= e($row['plan_name']) ?> (<?= e($row['plan_code']) ?>)</dd></div>
      <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">User</dt><dd class="col-span-2"><?= e($row['user_username'] ?? '(guest)') ?></dd></div>
      <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">Contact name</dt><dd class="col-span-2"><?= e($row['contact_name']) ?></dd></div>
      <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">Contact email</dt><dd class="col-span-2"><?= e($row['contact_email']) ?></dd></div>
      <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">Phone</dt><dd class="col-span-2"><?= e($row['contact_phone'] ?? '—') ?></dd></div>
      <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">Message</dt><dd class="col-span-2 whitespace-pre-line"><?= e($row['message'] ?? '') ?></dd></div>
      <?php if (!empty($row['admin_note'])): ?>
        <div class="py-2 grid grid-cols-3"><dt class="text-zinc-500">Admin note</dt><dd class="col-span-2 whitespace-pre-line"><?= e($row['admin_note']) ?></dd></div>
      <?php endif ?>
    </dl>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
    <h2 class="text-sm font-semibold mb-3">Resolve</h2>
    <?php if (in_array($row['status'], ['new','reviewing'], true)): ?>
      <form method="post" action="<?= e(admin_url('membership-requests/' . $row['id'] . '/approve')) ?>" class="space-y-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Expires at</label>
          <input type="datetime-local" name="expires_at" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5 text-sm">
        </div>
        <div>
          <label class="block text-xs text-zinc-500 mb-1">Note (visible to user)</label>
          <textarea name="admin_note" rows="3" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5 text-sm"></textarea>
        </div>
        <button class="w-full px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm">Approve &amp; activate</button>
      </form>
      <form method="post" action="<?= e(admin_url('membership-requests/' . $row['id'] . '/reject')) ?>" class="mt-4 space-y-3 border-t border-zinc-800 pt-4">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <textarea name="admin_note" rows="2" placeholder="Reason for rejection (optional)" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5 text-sm"></textarea>
        <button class="w-full px-3 py-2 rounded-lg border border-red-900/40 hover:bg-red-950/30 text-red-300 text-sm">Reject</button>
      </form>
    <?php else: ?>
      <div class="text-sm text-zinc-400">This request is already resolved.</div>
    <?php endif ?>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
