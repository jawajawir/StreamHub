<?php /** @var array $content @var array|null $source @var array $player @var array $decision
                @var array $tags @var array $categories @var array $performers @var array $related
                @var ?string $reaction @var bool $favorited @var int $countdown @var ?string $directUrl */
$loggedIn = (bool) ($auth_user ?? null);
$tier = ($auth_user['membership_tier'] ?? 'guest');
$canPlay = $decision['decision'] === \App\Services\AccessRuleService::DECISION_ALLOW;
ob_start(); ?>
<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2">

    <?php if ($canPlay): ?>
      <?php $needCountdown = !$loggedIn && $countdown > 0; ?>
      <div class="rounded-2xl overflow-hidden bg-black aspect-video relative" x-data="{ count: <?= (int) $countdown ?>, started: <?= $needCountdown ? 'false' : 'true' ?> }"
           x-init="$nextTick(() => { if (!started) { let t = setInterval(() => { count--; if (count <= 0) { clearInterval(t); started = true; } }, 1000); } })">
        <?php if ($player['type'] === 'iframe'): ?>
          <template x-if="started">
            <iframe src="<?= e($player['embed_url']) ?>" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen
              referrerpolicy="strict-origin-when-cross-origin" class="absolute inset-0 w-full h-full"></iframe>
          </template>
        <?php elseif ($player['type'] === 'direct'): ?>
          <link rel="stylesheet" href="https://vjs.zencdn.net/8.10.0/video-js.css">
          <template x-if="started">
            <video id="sh-vjs-player" class="video-js vjs-default-skin vjs-fluid absolute inset-0 w-full h-full"
              controls preload="metadata" poster="<?= e($content['thumbnail_url'] ?? '') ?>">
              <?php if ($directUrl): ?>
                <source src="<?= e($directUrl) ?>" type="<?= str_ends_with(strtolower($directUrl), '.m3u8') ? 'application/x-mpegURL' : 'video/mp4' ?>">
              <?php endif ?>
              <p class="vjs-no-js text-zinc-300 p-4">To view this video please enable JavaScript.</p>
            </video>
          </template>
          <script src="https://vjs.zencdn.net/8.10.0/video.min.js" defer></script>
          <?php if ($directUrl && str_ends_with(strtolower($directUrl), '.m3u8')): ?>
            <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js" defer></script>
          <?php endif ?>
          <script>
          window.addEventListener('DOMContentLoaded', () => {
            const init = () => {
              if (!window.videojs) return setTimeout(init, 100);
              const el = document.getElementById('sh-vjs-player');
              if (!el) return;
              window.videojs(el, { fluid: true, html5: { vhs: { overrideNative: true } } });
            };
            init();
          });
          </script>
        <?php else: ?>
          <div class="absolute inset-0 flex items-center justify-center text-zinc-400 text-sm">
            Video unavailable: <?= e($player['error'] ?? 'unknown source') ?>
          </div>
        <?php endif ?>

        <?php if ($needCountdown): ?>
          <div x-show="!started" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 text-white text-center p-4">
            <div class="text-5xl font-bold tabular-nums" x-text="count"></div>
            <div class="mt-2 text-sm text-zinc-300">Player will activate in a moment.</div>
            <a href="/login" class="mt-3 text-xs text-brand-400 hover:underline">Sign in to skip the countdown</a>
          </div>
        <?php endif ?>
      </div>
    <?php else: ?>
      <?php $reason = $decision['reason'] ?? null; ?>
      <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 aspect-video flex items-center justify-center text-center">
        <div>
          <?php if ($decision['decision'] === \App\Services\AccessRuleService::DECISION_REQUIRE_LOGIN): ?>
            <div class="mx-auto w-12 h-12 rounded-full bg-blue-600/15 border border-blue-600/30 flex items-center justify-center"><i data-lucide="lock" class="w-6 h-6 text-blue-400"></i></div>
            <h2 class="mt-3 text-lg font-semibold">Sign in to watch</h2>
            <p class="mt-1 text-sm text-zinc-400">This video is for registered members.</p>
            <a href="/login" class="mt-4 inline-flex px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium">Sign in</a>
          <?php elseif ($decision['decision'] === \App\Services\AccessRuleService::DECISION_REQUIRE_MEMBERSHIP): ?>
            <div class="mx-auto w-12 h-12 rounded-full bg-amber-600/15 border border-amber-600/30 flex items-center justify-center"><i data-lucide="crown" class="w-6 h-6 text-amber-400"></i></div>
            <h2 class="mt-3 text-lg font-semibold"><?= e(strtoupper($decision['required_tier'] ?? 'PREMIUM')) ?> only</h2>
            <p class="mt-1 text-sm text-zinc-400">Upgrade your membership to watch this video.</p>
            <a href="/membership" class="mt-4 inline-flex px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium">View membership plans</a>
          <?php else: ?>
            <div class="mx-auto w-12 h-12 rounded-full bg-zinc-800 flex items-center justify-center"><i data-lucide="alert-triangle" class="w-6 h-6 text-zinc-400"></i></div>
            <h2 class="mt-3 text-lg font-semibold">Video unavailable</h2>
            <p class="mt-1 text-sm text-zinc-400">This content cannot be played at the moment.</p>
          <?php endif ?>
        </div>
      </div>
    <?php endif ?>

    <header class="mt-5">
      <h1 class="text-xl sm:text-2xl font-semibold tracking-tight"><?= e($content['title']) ?></h1>
      <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-zinc-400">
        <?php if (!empty($content['view_count'])): ?>
          <span><?= number_format((int) $content['view_count']) ?> views</span>
        <?php endif ?>
        <?php if (!empty($content['published_at'])): ?>
          <span>· <?= e(date('M j, Y', strtotime((string) $content['published_at']))) ?></span>
        <?php endif ?>
        <?php if (!empty($categories)): ?>
          <span>·</span>
          <?php foreach ($categories as $c): ?>
            <a class="hover:text-zinc-200" href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </header>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <?php if ($loggedIn && \App\Services\FeatureToggleService::enabled('like_dislike')): ?>
        <form method="post" action="/watch/<?= (int) $content['id'] ?>/like" class="inline">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
          <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border <?= $reaction === 'like' ? 'border-emerald-600 text-emerald-300 bg-emerald-600/10' : 'border-zinc-800 hover:bg-zinc-800/50 text-zinc-300' ?> text-sm">
            <i data-lucide="thumbs-up" class="w-4 h-4"></i> <?= number_format((int) $content['like_count']) ?>
          </button>
        </form>
        <form method="post" action="/watch/<?= (int) $content['id'] ?>/dislike" class="inline">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
          <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border <?= $reaction === 'dislike' ? 'border-red-600 text-red-300 bg-red-600/10' : 'border-zinc-800 hover:bg-zinc-800/50 text-zinc-300' ?> text-sm">
            <i data-lucide="thumbs-down" class="w-4 h-4"></i> <?= number_format((int) $content['dislike_count']) ?>
          </button>
        </form>
      <?php endif ?>
      <?php if ($loggedIn && \App\Services\FeatureToggleService::enabled('favorites')): ?>
        <form method="post" action="/watch/<?= (int) $content['id'] ?>/favorite" class="inline">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
          <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border <?= $favorited ? 'border-pink-600 text-pink-300 bg-pink-600/10' : 'border-zinc-800 hover:bg-zinc-800/50 text-zinc-300' ?> text-sm">
            <i data-lucide="heart" class="w-4 h-4"></i> <?= $favorited ? 'Saved' : 'Save' ?>
          </button>
        </form>
      <?php elseif (!$loggedIn): ?>
        <a href="/login" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-800 hover:bg-zinc-800/50 text-sm text-zinc-300">
          <i data-lucide="user" class="w-4 h-4"></i> Sign in to react
        </a>
      <?php endif ?>
      <details class="ml-auto">
        <summary class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-800 hover:bg-zinc-800/50 text-sm text-zinc-300">
          <i data-lucide="flag" class="w-4 h-4"></i> Report
        </summary>
        <form method="post" action="/watch/<?= (int) $content['id'] ?>/report" class="mt-2 rounded-lg border border-zinc-800 bg-zinc-900/60 p-3 max-w-sm">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? csrf_token()) ?>">
          <textarea name="message" rows="3" maxlength="1000" placeholder="What's wrong?" class="w-full text-sm rounded bg-zinc-950 border border-zinc-800 p-2"></textarea>
          <button class="mt-2 px-3 py-1.5 rounded bg-brand-600 hover:bg-brand-500 text-white text-xs font-medium">Send report</button>
        </form>
      </details>
    </div>

    <?php if (!empty($content['description'])): ?>
      <div class="mt-5 text-sm text-zinc-300 leading-relaxed whitespace-pre-line"><?= e($content['description']) ?></div>
    <?php endif ?>

    <?php if (!empty($tags)): ?>
      <div class="mt-5 flex flex-wrap gap-2">
        <?php foreach ($tags as $t): ?>
          <a href="/tag/<?= e($t['slug']) ?>" class="px-2.5 py-1 rounded-full border border-zinc-800 bg-zinc-900/50 text-xs hover:border-brand-500/40 hover:text-brand-300">#<?= e($t['name']) ?></a>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <?php if (!empty($performers)): ?>
      <div class="mt-6">
        <h3 class="text-xs uppercase tracking-wider text-zinc-500 mb-2">Performers</h3>
        <div class="flex flex-wrap gap-3">
          <?php foreach ($performers as $p): ?>
            <a href="/performer/<?= e($p['slug']) ?>" class="flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900/40 px-2 py-1.5 hover:border-brand-500/40">
              <?php if (!empty($p['avatar_url'])): ?><img src="<?= e($p['avatar_url']) ?>" alt="" class="w-6 h-6 rounded-full object-cover"><?php else: ?><div class="w-6 h-6 rounded-full bg-zinc-800"></div><?php endif ?>
              <span class="text-xs"><?= e($p['name']) ?></span>
            </a>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>

  </div>

  <aside class="lg:col-span-1">
    <h3 class="text-xs uppercase tracking-wider text-zinc-500 mb-3">Related videos</h3>
    <?php if (empty($related)): ?>
      <div class="text-sm text-zinc-500">No related content yet.</div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($related as $r): ?>
          <a href="/watch/<?= e($r['slug']) ?>" class="flex gap-3 group">
            <div class="w-32 aspect-video rounded-lg overflow-hidden bg-zinc-900 border border-zinc-800 shrink-0">
              <?php if (!empty($r['thumbnail_url'])): ?><img src="<?= e($r['thumbnail_url']) ?>" alt="" loading="lazy" class="w-full h-full object-cover"><?php endif ?>
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm line-clamp-2 group-hover:text-brand-300"><?= e($r['title']) ?></div>
              <div class="mt-1 text-[11px] text-zinc-500"><?= number_format((int) $r['view_count']) ?> views</div>
            </div>
          </a>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </aside>
</div>
<?php $content_html = ob_get_clean(); $content = $content_html; include __DIR__ . '/../layouts/app.php';
