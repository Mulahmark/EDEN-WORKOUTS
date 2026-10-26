<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_head('Fitness Services');
public_nav('services.php');
$services = [
    ['Personal training (1-on-1)', 'A dedicated coach, a written programme and weekly review of your logs.', 'Quoted per package'],
    ['Small-group strength', 'Maximum six people per coach. Same programming quality, shared cost.', 'Quoted per session'],
    ['Programme design only', 'Already training on your own? We build the plan, you run it.', 'Quoted per programme'],
    ['Body composition assessment', 'Weight, measurements and body-fat estimate recorded in your dashboard.', 'Quoted per session'],
    ['Conditioning blocks', 'Rower, bike and sled intervals for endurance and fat loss.', 'Included in most plans'],
    ['Member progress portal', 'Your sessions, payments, plans and charts available online.', 'Included with membership'],
];
?>
<div class="container section">
  <span class="eyebrow">Services</span>
  <h1 class="section-title mt-3">Fitness services</h1>
  <p class="section-sub mt-2">Service pricing is confirmed by the gym administrator; membership subscriptions are billed separately.</p>
  <div class="row g-4 mt-2">
    <?php foreach ($services as [$t, $d, $p]): ?>
      <div class="col-md-6 col-lg-4"><div class="card-eden">
        <h3 class="mt-0"><?= e($t) ?></h3><p><?= e($d) ?></p><p class="text-eden small mt-3"><?= e($p) ?></p>
      </div></div>
    <?php endforeach; ?>
  </div>
  <div class="card-eden mt-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <span class="text-muted-eden">Ask at reception for a written quote before booking.</span>
    <a href="<?= e(url('contact.php')) ?>" class="btn btn-eden btn-sm">Request a quote</a>
  </div>
  <div class="row g-4 mt-4">
    <div class="col-md-6"><div class="img-frame"><img src="<?= e(asset('images/photos/group-class.jpg')) ?>" alt="Group class in the EDEN WORKOUTS studio" width="1400" height="900" loading="lazy"></div></div>
    <div class="col-md-6"><div class="img-frame"><img src="<?= e(asset('images/photos/deadlift.jpg')) ?>" alt="Strength training session" width="1400" height="900" loading="lazy"></div></div>
  </div>
  <p class="hint mt-4">Training and tracking features support your fitness programme. They are not medical advice — consult a qualified health professional before starting a new programme.</p>
</div>
<?php public_footer(); ?>
