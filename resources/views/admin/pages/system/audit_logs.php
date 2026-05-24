<?php /** @var array $rows @var int $page @var int $pages @var int $total */
ob_start();
$heading = 'Audit logs'; $sub = number_format($total) . ' entries';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="text-xs text-zinc-500 bg-zinc-900/60"><tr>
      <th class="text-left px-4 py-2">When</th><th class="text-left px-4 py-2">Admin</th><th class="text-left px-4 py-2">Action</th>
      <th class="text-left px-4 py-2">Entity</th><th class="text-left px-4 py-2">After</th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-800/70">
      <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center py-10 text-zinc-500">No audit entries.</td></tr><?php endif ?>
      <?php foreach ($rows as $r): ?>
        <tr class="text-xs align-top">
          <td class="px-4 py-2 text-zinc-400"><?= e((string) $r['created_at']) ?></td>
          <td class="px-4 py-2"><?= e($r['admin_username'] ?? '—') ?></td>
          <td class="px-4 py-2 font-mono"><?= e($r['action']) ?></td>
          <td class="px-4 py-2"><?= e((string) ($r['entity_type'] ?? '—')) ?>#<?= e((string) ($r['entity_id'] ?? '')) ?></td>
          <td class="px-4 py-2 text-[11px] text-zinc-400 max-w-md truncate"><?= e((string) ($r['after_json'] ?? '')) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php $baseUrl = admin_url('system/audit-logs'); include __DIR__ . '/../../components/pagination.php'; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
