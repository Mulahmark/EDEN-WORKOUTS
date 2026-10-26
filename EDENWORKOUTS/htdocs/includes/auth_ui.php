<?php
declare(strict_types=1);

/**
 * Shared look for the sign-in and registration pages.
 * The photo panel automatically uses every image found in assets/images/photos/
 * (jpg, jpeg, png, webp) and crossfades through up to 5 of them, so you can add or
 * remove photos without editing any code.
 */
function auth_photos(int $max = 5): array
{
    $dir = __DIR__ . '/../assets/images/photos/';
    $files = [];
    foreach (['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'] as $ext) {
        foreach (glob($dir . '*.' . $ext) ?: [] as $f) $files[basename($f)] = true;
    }
    $names = array_keys($files);
    sort($names);
    if (!$names) return [];
    // Start from a different photo on each visit so the page does not always open on the same image.
    $shift = random_int(0, count($names) - 1);
    $names = array_merge(array_slice($names, $shift), array_slice($names, 0, $shift));
    return array_map(fn($n) => asset('images/photos/' . rawurlencode($n)), array_slice($names, 0, $max));
}

function auth_styles(): void
{
    $photos = auth_photos();
    $n = max(1, count($photos));
    $secs = 6;                              // seconds each photo stays on screen
    $total = $n * $secs;
    $hold = 100 / $n;                       // % of the cycle each photo is "on"
    $fade = min(6, $hold / 3);              // % used for fading
    ?>
<style>
  .auth { min-height: 100vh; display: grid; grid-template-columns: 1.05fr 1fr; }
  .auth-visual { position: relative; display: flex; flex-direction: column; justify-content: space-between;
    padding: 40px 48px; overflow: hidden; color: #fff; background: var(--gunmetal); isolation: isolate; }
  .auth-slides, .auth-slides::after { position: absolute; inset: 0; z-index: -1; }
  .auth-slides::after { content: ''; background:
      linear-gradient(180deg, rgba(21,25,29,.58) 0%, rgba(21,25,29,.30) 40%, rgba(21,25,29,.93) 100%),
      linear-gradient(135deg, rgba(255,107,53,.26), transparent 55%); }
  .auth-slide { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0; }
<?php foreach ($photos as $i => $src): ?>
  .auth-slide:nth-child(<?= $i + 1 ?>) { background-image: url('<?= e($src) ?>');<?php if ($n > 1): ?> animation: authFade <?= $total ?>s infinite; animation-delay: <?= $i * $secs ?>s;<?php else: ?> opacity: 1;<?php endif; ?> }
<?php endforeach; ?>
<?php if ($n > 1): ?>
  @keyframes authFade {
    0% { opacity: 0; }
    <?= round($fade, 2) ?>% { opacity: 1; }
    <?= round($hold, 2) ?>% { opacity: 1; }
    <?= round($hold + $fade, 2) ?>% { opacity: 0; }
    100% { opacity: 0; }
  }
  @media (prefers-reduced-motion: reduce) { .auth-slide { animation: none !important; } .auth-slide:first-child { opacity: 1; } }
<?php endif; ?>
  .auth-visual h2 { font-size: clamp(1.9rem, 3.4vw, 3rem); line-height: 1.05; margin: 0 0 14px; max-width: 18ch; }
  .auth-visual h2 span { color: var(--orange-soft); }
  .auth-visual p { color: #d4d9de; max-width: 36ch; margin: 0 0 22px; }
  .auth-points { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; font-size: .9rem; color: #e8ebee; }
  .auth-points li { display: flex; align-items: center; gap: 10px; }
  .auth-points i { color: var(--orange-soft); font-size: 1.05rem; }

  .auth-panel { display: flex; align-items: center; justify-content: center; padding: 40px 24px;
    background: radial-gradient(700px 360px at 85% 0%, rgba(255,107,53,.12), transparent 65%), var(--charcoal); }
  .auth-box { width: 100%; max-width: 440px; }
  .auth-box.wide { max-width: 520px; }
  .auth-back { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; color: var(--muted); margin-bottom: 26px; }
  .auth-back:hover { color: var(--orange-soft); }
  .auth-box h1 { font-size: 2rem; margin: 0 0 6px; }
  .auth-sub { color: var(--muted); margin: 0 0 24px; font-size: .95rem; }
  .auth-box .alert-eden { margin-bottom: 18px; }
  .auth-box .field { margin-bottom: 16px; }
  .auth-box .field label { display: flex; justify-content: space-between; align-items: baseline; }
  .auth-box .field label a { text-transform: none; letter-spacing: 0; font-weight: 600; font-size: .78rem; }
  .auth-input { position: relative; }
  .auth-input > i.lead-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
  .auth-input .input { padding: 13px 44px 13px 42px; font-size: .95rem; border-radius: 12px; }
  .auth-input.no-toggle .input { padding-right: 14px; }
  .auth-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: 0; color: var(--muted);
    width: 36px; height: 36px; border-radius: 8px; cursor: pointer; }
  .auth-toggle:hover { color: var(--ink); background: rgba(255,255,255,.06); }
  .auth-submit { width: 100%; padding: 13px 20px; font-size: 1rem; border-radius: 12px; margin-top: 6px; }
  .auth-foot { margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--line); text-align: center; font-size: .88rem; color: var(--muted); }
  .auth-foot a { font-weight: 700; }
  .auth-plan { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; padding: 12px 16px;
    border: 1px solid rgba(255,107,53,.35); background: rgba(255,107,53,.08); border-radius: 12px; }
  .auth-plan small { display: block; color: var(--muted); letter-spacing: .08em; text-transform: uppercase; font-size: .65rem; }
  .auth-plan strong { font-size: 1rem; }
  .auth-plan .price { color: var(--orange-soft); font-weight: 700; white-space: nowrap; font-size: .9rem; }
  .auth-meter { height: 4px; border-radius: 4px; background: var(--line); margin-top: 8px; overflow: hidden; }
  .auth-meter span { display: block; height: 100%; width: 0; background: var(--bad); transition: width .2s, background .2s; }
  .auth-note { font-size: .78rem; color: var(--muted); margin: 14px 0 0; display: flex; gap: 8px; align-items: flex-start; }
  .auth-note i { color: var(--orange-soft); margin-top: 2px; }
  .auth-match { font-size: .72rem; margin-top: 5px; min-height: 1em; }

  @media (max-width: 900px) {
    .auth { grid-template-columns: 1fr; }
    .auth-visual { min-height: 180px; padding: 24px; }
    .auth-visual h2, .auth-visual p, .auth-points { display: none; }
    .auth-panel { align-items: flex-start; padding: 28px 20px 44px; }
  }
</style>
<?php
}

/** Left-hand photo panel. $points is a list of ['bi-icon-name', 'text']. */
function auth_visual(string $headline, string $headlineAccent, string $text, array $points): void
{
    $photos = auth_photos();
    ?>
  <aside class="auth-visual">
    <div class="auth-slides" aria-hidden="true"><?php foreach ($photos as $_) echo '<div class="auth-slide"></div>'; ?></div>
    <a class="brand-link" href="<?= e(url('index.php')) ?>"><?= brand(44) ?></a>
    <div>
      <h2><?= e($headline) ?> <span><?= e($headlineAccent) ?></span></h2>
      <p><?= e($text) ?></p>
      <ul class="auth-points">
        <?php foreach ($points as [$icon, $label]): ?><li><i class="bi <?= e($icon) ?>"></i> <?= e($label) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </aside>
<?php
}

/** Small script shared by both pages: eye buttons on every .auth-toggle. */
function auth_scripts(): void
{
    ?>
<script>
  document.querySelectorAll('.auth-toggle').forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute('data-for'));
    if (!input) return;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      btn.firstElementChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });
</script>
<?php
}
