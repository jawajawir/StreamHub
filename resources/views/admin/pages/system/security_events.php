<?php /** @var array $rows @var int $page @var int $pages @var int $total */
ob_start();
$heading = 'Security events'; $sub = number_format($total) . ' events';
include __DIR__ . '/../../components/page_header.php';
?>
<div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
  <table class="w-full text-sm">
    <thead class="text-xs text-zinc-500 bg-zinc-900/60"><tr>
      <th class="text-left px-4 py-2">When</th><th class="text-left px-4 py-2">Type</th><th class="text-left px-4 py-2">Severity</th>
      <th class="text-left px-4 py-2">Path</th><th class="text-left px-4 py-2">IP hash</th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-800/70">
      <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center py-10 text-zinc-500">No events recorded.</td></tr><?php endif ?>
      <?php foreach ($rows as $r): $sc = match ($r['severity']) { 'critical','error' => 'red', 'warning' => 'amber', default => 'zinc' }; ?>
        <tr class="text-xs">
          <td class="px-4 py-2 text-zinc-400"><?= e((string) $r['created_at']) ?></td>
          <td class="px-4 py-2 font-mono"><?= e($r['event_type']) ?></td>
          <td class="px-4 py-2"><span class="px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300"><?= e($r['severity']) ?></span></td>
          <td class="px-4 py-2 font-mono text-zinc-400 max-w-xs truncate"><?= e($r['request_path'] ?? '') ?></td>
          <td class="px-4 py-2 font-mono text-zinc-500 truncate"><?= e(substr((string) ($r['ip_hash'] ?? ''), 0, 12)) ?>…</td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php $baseUrl = admin_url('system/security-events'); include __DIR__ . '/../../components/pagination.php'; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php';
