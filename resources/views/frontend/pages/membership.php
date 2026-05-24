<?php /** @var array $plans @var ?array $myRequest */
$errors = \App\Core\Session::instance()->pull('_errors', []);
$old    = \App\Core\Session::instance()->pull('_old_input', []);
ob_start(); ?>
<header class="text-center max-w-2xl mx-auto mb-10">
  <h1 class="text-3xl font-semibold tracking-tight">Membership</h1>
  <p class="mt-2 text-sm text-zinc-400">Choose a plan and submit a request. We'll contact you to complete activation manually.</p>
</header>

<div class="grid sm:grid-cols-3 gap-4 max-w-5xl mx-auto">
<?php foreach ($plans as $plan):
  $featured = $plan['code'] === 'premium';
  $bullets = match ($plan['code']) {
    'free'    => ['All ads visible', 'Basic member features', 'Watch history & favorites'],
    'premium' => ['Reduced ads', 'Higher quality playback', 'All Free features'],
    'vip'     => ['No ads', 'VIP-only content access', 'All Premium features'],
    default   => [],
  };
?>
  <div class="rounded-2xl border <?= $featured ? 'border-brand-600/60 bg-gradient-to-br from-brand-950/40 to-zinc-900' : 'border-zinc-800 bg-zinc-900/50' ?> p-6 relative">
    <?php if ($featured): ?><div class="absolute -top-3 left-6 text-[10px] uppercase tracking-wider px-2 py-0.5 rounded bg-brand-600 text-white">Recommended</div><?php endif ?>
    <div class="text-xs uppercase tracking-wider text-zinc-500"><?= e($plan['code']) ?></div>
    <h2 class="mt-1 text-2xl font-semibold tracking-tight"><?= e($plan['name']) ?></h2>
    <p class="mt-2 text-sm text-zinc-400"><?= e($plan['description'] ?? '') ?></p>
    <ul class="mt-4 space-y-1.5 text-sm">
      <?php foreach ($bullets as $b): ?>
        <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 mt-0.5 text-emerald-400"></i><span><?= e($b) ?></span></li>
      <?php endforeach ?>
    </ul>
  </div>
<?php endforeach ?>
</div>

<?php if ($myRequest): ?>
  <div class="mt-10 max-w-3xl mx-auto rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
    <div class="text-xs uppercase tracking-wider text-zinc-500 mb-1">Your latest request</div>
    <div class="flex items-center gap-3 flex-wrap">
      <span class="text-sm font-medium"><?= e($myRequest['plan_name']) ?></span>
      <?php $sc = match ($myRequest['status']) { 'approved' => 'emerald', 'rejected' => 'red', 'reviewing' => 'amber', default => 'zinc' }; ?>
      <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded bg-<?= $sc ?>-500/15 text-<?= $sc ?>-300 border border-<?= $sc ?>-500/30"><?= e($myRequest['status']) ?></span>
      <span class="text-xs text-zinc-500"><?= e($myRequest['created_at']) ?></span>
    </div>
    <?php if (!empty($myRequest['admin_note'])): ?>
      <div class="mt-3 text-sm text-zinc-300 whitespace-pre-line"><?= e($myRequest['admin_note']) ?></div>
    <?php endif ?>
  </div>
<?php endif ?>

<div class="mt-10 max-w-2xl mx-auto rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6">
  <h2 class="text-lg font-semibold tracking-tight">Request membership upgrade</h2>
  <p class="mt-1 text-sm text-zinc-400">Send your details. Our team will reach out to arrange manual payment.</p>
  <form method="post" action="/membership/request" class="mt-5 grid sm:grid-cols-2 gap-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
    <div class="sm:col-span-2">
      <label class="block text-xs font-medium text-zinc-400 mb-1">Plan</label>
      <select name="plan" required class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
        <?php foreach ($plans as $p): if ($p['code'] === 'free') continue; ?>
          <option value="<?= e($p['code']) ?>"><?= e($p['name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Your name</label>
      <input name="contact_name" required value="<?= e($old['contact_name'] ?? ($auth_user['display_name'] ?? '')) ?>" class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['contact_name']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-xs font-medium text-zinc-400 mb-1">Contact email</label>
      <input name="contact_email" type="email" required value="<?= e($old['contact_email'] ?? ($auth_user['email'] ?? '')) ?>" class="w-full rounded-lg bg-zinc-950 border <?= !empty($errors['contact_email']) ? 'border-red-700' : 'border-zinc-800' ?> px-3 py-2 text-sm">
    </div>
    <div class="sm:col-span-2">
      <label class="block text-xs font-medium text-zinc-400 mb-1">Phone (optional)</label>
      <input name="contact_phone" value="<?= e($old['contact_phone'] ?? '') ?>" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm">
    </div>
    <div class="sm:col-span-2">
      <label class="block text-xs font-medium text-zinc-400 mb-1">Message</label>
      <textarea name="message" rows="4" maxlength="2000" class="w-full rounded-lg bg-zinc-950 border border-zinc-800 px-3 py-2 text-sm"><?= e($old['message'] ?? '') ?></textarea>
    </div>
    <div class="sm:col-span-2 flex items-center justify-end">
      <button class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Submit request</button>
    </div>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php';
