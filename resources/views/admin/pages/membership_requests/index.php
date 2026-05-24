<?php /** @var array $rows @var string $status */
ob_start();
$heading = 'Membership requests'; $sub = count($rows) . ' results';
include __DIR__ . '/../../components/page_header.php';
?>
<form method="get" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-3 mb-4 flex flex-wrap gap-2 text-sm">
  <select name="status" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
    <option value="">All</option>
    <?php foreach (['new','reviewing','approved','rejected','cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach ?>
  </select>
  <button class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Filter</button>
</form>

<div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="text-left text-xs text-zinc-500 bg-zinc-900/60"><tr>
      <th class="px-4 py-2.5">Submitted</th><th class="px-4 py-2.5">User</th><th class="px-4 py-2.5">Plan</th>
      <th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-800/70">
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center py-10 text-zinc-500">No requests.</td></tr>
      <?php endif ?>
      <?php foreach ($rows as $r): $sc = match ($r['status']) { 'approved' => 'emerald', 'rejected' => 'red', 'reviewing' => 'amber', default => 'zinc' }; ?>
        <tr>
          <td class="px-4 py-2 text-xs text-zinc-400"><?= e((string) $r['created_at']) ?></td>
          <td class="px-4 py-2"><?= e($r['user_username'] ?? '(guest)') ?><div class="text-xs text-zinc-500"><?= e($r['contact_email']) ?></div></td>
          <td class="px-4 py-2"><?= e($r['plan_name']) ?></td>
          <td class="px-4 py-2"><span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 border border-<?= $sc ?>-500/30"><?= e($r['status']) ?></span></td>
          <td class="px-4 py-2 text-right"><a href="<?= e(admin_url('membership-requests/' . $r['id'])) ?>" class="text-xs text-brand-400 hover:text-brand-300">Open</a></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
