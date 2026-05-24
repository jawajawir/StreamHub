<?php /** @var int $totalContent @var int $publishedContent @var int $totalUsers @var int $premiumUsers @var int $vipUsers
                @var int $pendingRequests @var array $viewsByDay @var array $tierDist @var array $topContent @var array $recentAudit
                @var array $recentSecurity @var ?array $lastSync @var ?array $doodCred @var int $queuePending @var int $queueFailed
                @var array $crons @var array $healthLatest */
ob_start(); ?>
<header class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
  <p class="text-sm text-zinc-400 mt-0.5">Snapshot of <?= e($app_name ?? 'StreamHub') ?> as of <?= e(date('M j, Y H:i')) ?>.</p>
</header>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <?php
  $kpis = [
    ['label' => 'Total content',   'value' => $totalContent,     'icon' => 'film',      'sub' => "$publishedContent published"],
    ['label' => 'Total users',     'value' => $totalUsers,       'icon' => 'users',     'sub' => 'all members'],
    ['label' => 'Premium / VIP',   'value' => $premiumUsers + $vipUsers, 'icon' => 'crown', 'sub' => "P:$premiumUsers · V:$vipUsers"],
    ['label' => 'Pending requests','value' => $pendingRequests,  'icon' => 'mail-question', 'sub' => 'membership inbox'],
  ];
  foreach ($kpis as $k): ?>
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
      <div class="flex items-center justify-between">
        <div class="text-xs text-zinc-500"><?= e($k['label']) ?></div>
        <i data-lucide="<?= e($k['icon']) ?>" class="w-4 h-4 text-zinc-500"></i>
      </div>
      <div class="mt-2 text-2xl font-semibold tabular-nums"><?= number_format($k['value']) ?></div>
      <div class="text-[11px] text-zinc-500 mt-0.5"><?= e($k['sub']) ?></div>
    </div>
  <?php endforeach ?>
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-6">
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4 lg:col-span-2">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-semibold">Views — last 14 days</h2>
      <span class="text-[11px] text-zinc-500">Source: content_views</span>
    </div>
    <canvas id="chartViews" height="120"></canvas>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
    <h2 class="text-sm font-semibold mb-3">Membership distribution</h2>
    <canvas id="chartTiers" height="180"></canvas>
  </div>
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-6">
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
    <h2 class="text-sm font-semibold mb-3">Top viewed content</h2>
    <?php if (empty($topContent)): ?>
      <div class="text-sm text-zinc-500 py-6 text-center">No content yet.</div>
    <?php else: ?>
      <ul class="text-sm divide-y divide-zinc-800">
        <?php foreach ($topContent as $c): ?>
          <li class="py-2 flex items-center justify-between gap-3">
            <a href="<?= e(admin_url('videos/' . (int) $c['id'] . '/edit')) ?>" class="line-clamp-1 hover:text-brand-300"><?= e($c['title']) ?></a>
            <div class="text-xs text-zinc-500 tabular-nums shrink-0"><?= number_format((int) $c['view_count']) ?></div>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
    <h2 class="text-sm font-semibold mb-3">Doodstream sync</h2>
    <?php if (!$doodCred): ?>
      <div class="text-sm text-zinc-400">No API key configured.</div>
      <a href="<?= e(admin_url('api/doodstream')) ?>" class="mt-3 inline-flex items-center gap-1 text-xs text-brand-400 hover:text-brand-300">Configure API <i data-lucide="arrow-right" class="w-3 h-3"></i></a>
    <?php else: ?>
      <div class="text-xs text-zinc-500">Key (masked)</div>
      <div class="font-mono text-sm"><?= e((string) $doodCred['masked_value']) ?></div>
      <div class="mt-2 text-xs text-zinc-500">Last test: <?= e((string) ($doodCred['last_test_status'] ?? 'unknown')) ?> · <?= e((string) ($doodCred['last_tested_at'] ?? 'never')) ?></div>
      <?php if ($lastSync): ?>
        <div class="mt-3 text-xs text-zinc-400">Last sync: <strong class="text-zinc-200"><?= e($lastSync['status']) ?></strong> · <?= e($lastSync['created_at']) ?></div>
        <div class="text-xs text-zinc-500">created <?= (int) $lastSync['created_files'] ?> · updated <?= (int) $lastSync['updated_files'] ?> · failed <?= (int) $lastSync['failed_files'] ?></div>
      <?php else: ?>
        <div class="mt-3 text-xs text-zinc-500">No sync runs yet.</div>
      <?php endif ?>
    <?php endif ?>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
    <h2 class="text-sm font-semibold mb-3">Queue &amp; cron</h2>
    <div class="grid grid-cols-2 gap-2 text-sm">
      <div class="rounded-lg border border-zinc-800 p-3">
        <div class="text-xs text-zinc-500">Pending jobs</div><div class="text-xl font-semibold"><?= (int) $queuePending ?></div>
      </div>
      <div class="rounded-lg border border-zinc-800 p-3">
        <div class="text-xs text-zinc-500">Failed jobs</div><div class="text-xl font-semibold <?= $queueFailed > 0 ? 'text-red-400' : '' ?>"><?= (int) $queueFailed ?></div>
      </div>
    </div>
    <ul class="mt-3 text-xs space-y-1.5">
      <?php foreach ($crons as $c): $sc = match ($c['last_status']) { 'success' => 'emerald', 'failed' => 'red', 'running' => 'amber', default => 'zinc' }; ?>
        <li class="flex items-center justify-between">
          <span class="font-mono text-zinc-300"><?= e($c['job_key']) ?></span>
          <span class="px-1.5 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 text-[10px] uppercase"><?= e($c['last_status']) ?></span>
        </li>
      <?php endforeach ?>
    </ul>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
    <h2 class="text-sm font-semibold mb-3">Recent admin activity</h2>
    <?php if (empty($recentAudit)): ?>
      <div class="text-sm text-zinc-500 py-6 text-center">No audit entries yet.</div>
    <?php else: ?>
      <ul class="text-sm divide-y divide-zinc-800">
        <?php foreach ($recentAudit as $a): ?>
          <li class="py-2 flex items-start gap-3">
            <div class="w-7 h-7 rounded bg-zinc-800 flex items-center justify-center text-[10px] uppercase text-zinc-300"><?= e(substr((string) ($a['admin_username'] ?? '?'), 0, 2)) ?></div>
            <div class="min-w-0 flex-1">
              <div class="font-mono text-xs text-zinc-200"><?= e($a['action']) ?></div>
              <div class="text-[11px] text-zinc-500"><?= e($a['admin_username'] ?? 'system') ?> · <?= e((string) $a['created_at']) ?> · <?= e((string) ($a['entity_type'] ?? '')) ?>#<?= e((string) ($a['entity_id'] ?? '—')) ?></div>
            </div>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </div>
  <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
    <h2 class="text-sm font-semibold mb-3">Security events</h2>
    <?php if (empty($recentSecurity)): ?>
      <div class="text-sm text-zinc-500 py-6 text-center">No events recorded.</div>
    <?php else: ?>
      <ul class="text-sm divide-y divide-zinc-800">
        <?php foreach ($recentSecurity as $s): $sc = match ($s['severity']) { 'critical' => 'red', 'error' => 'red', 'warning' => 'amber', default => 'zinc' }; ?>
          <li class="py-2 flex items-start gap-3">
            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-<?= $sc ?>-500 shrink-0"></span>
            <div class="min-w-0 flex-1">
              <div class="font-mono text-xs"><?= e($s['event_type']) ?></div>
              <div class="text-[11px] text-zinc-500"><?= e($s['severity']) ?> · <?= e($s['request_path'] ?: '—') ?> · <?= e((string) $s['created_at']) ?></div>
            </div>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
  const init = () => {
    if (!window.Chart) return setTimeout(init, 80);
    const labels = <?= json_encode(array_keys($viewsByDay)) ?>;
    const data   = <?= json_encode(array_values($viewsByDay)) ?>;
    const tiers  = <?= json_encode($tierDist) ?>;

    new Chart(document.getElementById('chartViews'), {
      type: 'line',
      data: { labels, datasets: [{ label: 'Views', data, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.15)', tension: 0.3, fill: true }] },
      options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#71717a' }, grid: { color: 'rgba(63,63,70,.4)' } }, y: { ticks: { color: '#71717a' }, grid: { color: 'rgba(63,63,70,.4)' }, beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartTiers'), {
      type: 'doughnut',
      data: { labels: ['Free','Premium','VIP'], datasets: [{ data: [tiers.free||0, tiers.premium||0, tiers.vip||0], backgroundColor: ['#3f3f46','#f59e0b','#a855f7'], borderWidth: 0 }] },
      options: { plugins: { legend: { labels: { color: '#a1a1aa' } } }, cutout: '68%' }
    });
  };
  init();
});
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
