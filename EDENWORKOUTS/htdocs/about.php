<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_head('About Us');
public_nav('about.php');
$values = [
    ['Consistency over intensity', 'Showing up four times a week beats one heroic session a month.'],
    ['Coaching with receipts', 'Every programme decision is written down and measurable.'],
    ['Respect for the floor', 'Rerack, wipe down, encourage the person next to you.'],
    ['Progress you can see', 'Sessions, measurements and goals tracked in your own dashboard.'],
];
$facilities = [
    ['Strength zone', 'Racks, platforms and dumbbells alongside selectorised leg and back machines.'],
    ['Conditioning floor', 'Rowers, bikes, sleds and open turf for intervals.'],
    ['Coaching studio', 'For assessments, mobility and small-group sessions on mats.'],
    ['Reception desk', 'Fast check-in, payments and membership support.'],
];
?>
<div class="container section">
  <span class="eyebrow">About <?= e(setting('gym_name', 'EDEN WORKOUTS')) ?></span>
  <h1 class="section-title mt-3">A gym built around measurable progress</h1>
  <div class="row g-4 mt-2">
    <div class="col-lg-7">
      <p class="text-muted-eden"><?= e(setting('about_text', 'EDEN WORKOUTS is a strength and conditioning gym focused on measurable progress.')) ?></p>
      <h3 class="mt-4">Our mission</h3>
      <p class="text-muted-eden"><?= e(setting('mission_text', 'To make professional coaching, structured training and measurable progress accessible.')) ?></p>
      <h3 class="mt-4">Our philosophy</h3>
      <p class="text-muted-eden">Training works when it is structured, supervised and reviewed. We write the plan, we coach the session, we log the result — then we adjust. Nothing here is improvised, and nothing is hidden from you.</p>
      <a href="<?= e(url('memberships.php')) ?>" class="btn btn-eden mt-3">View memberships</a>
      <a href="<?= e(url('contact.php')) ?>" class="btn btn-eden-ghost mt-3">Ask a question</a>
    </div>
    <div class="col-lg-5">
      <div class="img-frame mb-4"><img src="<?= e(asset('images/photos/gym-floor.jpg')) ?>" alt="EDEN WORKOUTS training floor" width="900" height="600" loading="lazy"></div>
      <div class="card-eden"><h3 class="mt-0">The facility</h3>
        <ul class="plan-benefits">
          <?php foreach ($facilities as [$t, $d]): ?><li><i class="bi bi-check-circle-fill"></i><span><strong><?= e($t) ?></strong> — <?= e($d) ?></span></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <h2 class="section-title mt-5">What we stand for</h2>
  <div class="row g-4 mt-1">
    <?php foreach ($values as [$t, $d]): ?>
      <div class="col-md-6"><div class="card-eden"><h3 class="mt-0 text-eden"><?= e($t) ?></h3><p><?= e($d) ?></p></div></div>
    <?php endforeach; ?>
  </div>
  <div class="row g-4 mt-4">
    <div class="col-md-6"><div class="img-frame"><img src="<?= e(asset('images/photos/group-class.jpg')) ?>" alt="Group training class in the EDEN WORKOUTS studio" width="1400" height="900" loading="lazy"></div></div>
    <div class="col-md-6"><div class="img-frame"><img src="<?= e(asset('images/photos/dumbbell-rack.jpg')) ?>" alt="Dumbbell rack in the EDEN WORKOUTS free-weights area" width="1000" height="1400" loading="lazy"></div></div>
  </div>
</div>
<?php public_footer(); ?>
