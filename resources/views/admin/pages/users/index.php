<?php /** @var array $rows @var int $page @var int $pages @var int $total @var int $perPage
                @var string $q @var string $tier @var string $status */
ob_start();
include __DIR__ . '/../../components/page_header.php' === false ? '' : null;
$heading = 'User Manager'; $sub = number_format($total) . ' users';
include __DIR__ . '/../../components/page_header.php';
?>
<form method="get" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-3 mb-4 flex flex-wrap gap-2 text-sm">
  <input name="q" value="<?= e($q) ?>" placeholder="Search username, email, name" class="flex-1 min-w-[180px] rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
  <select name="tier" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
    <option value="">All tiers</option>
    <?php foreach (['free','premium','vip'] as $t): ?><option value="<?= $t ?>" <?= $tier === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option><?php endforeach ?>
  </select>
  <select name="status" class="rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-1.5">
    <option value="">All statuses</option>
    <?php foreach (['active','pending_email','suspended','banned'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(str_replace('_',' ',$s)) ?></option><?php endforeach ?>
  </select>
  <button class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white">Filter</button>
</form>

<div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-left text-xs text-zinc-500 bg-zinc-900/60 sticky top-0">
        <tr>
          <th class="px-4 py-2.5">Username</th><th class="px-4 py-2.5">Email</th>
          <th class="px-4 py-2.5">Tier</th><th class="px-4 py-2.5">Status</th>
          <th class="px-4 py-2.5">Last login</th><th class="px-4 py-2.5">Created</th>
          <th class="px-4 py-2.5"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-zinc-800/70">
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center py-10 text-zinc-500">No users match.</td></tr>
        <?php endif ?>
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-zinc-800/30">
            <td class="px-4 py-2 font-medium"><?= e($r['username']) ?></td>
            <td class="px-4 py-2 text-zinc-300"><?= e($r['email']) ?></td>
            <td class="px-4 py-2"><?php $tc = match ($r['membership_tier']) { 'vip' => 'fuchsia', 'premium' => 'amber', default => 'zinc' }; ?>
              <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $tc ?>-500/15 text-<?= $tc ?>-300 border border-<?= $tc ?>-500/30"><?= e($r['membership_tier']) ?></span>
            </td>
            <td class="px-4 py-2"><?php $sc = match ($r['status']) { 'active' => 'emerald', 'banned' => 'red', 'suspended' => 'red', default => 'zinc' }; ?>
              <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 border border-<?= $sc ?>-500/30"><?= e(str_replace('_',' ',$r['status'])) ?></span>
            </td>
            <td class="px-4 py-2 text-xs text-zinc-400"><?= e($r['last_login_at'] ?? '—') ?></td>
            <td class="px-4 py-2 text-xs text-zinc-400"><?= e(date('M j, Y', strtotime((string) $r['created_at']))) ?></td>
            <td class="px-4 py-2 text-right">
              <a href="<?= e(admin_url('users/' . $r['id'])) ?>" class="text-xs text-brand-400 hover:text-brand-300 inline-flex items-center gap-1">View <i data-lucide="arrow-right" class="w-3 h-3"></i></a>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$baseUrl = admin_url('users') . '?q=' . rawurlencode($q) . '&tier=' . rawurlencode($tier) . '&status=' . rawurlencode($status);
include __DIR__ . '/../../components/pagination.php';
$content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
