<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_head('Personal Trainers');
public_nav('trainers.php');
$rows = db_all("SELECT * FROM trainers WHERE is_public = 1 AND employment_status = 'active' ORDER BY experience_years DESC, full_name");
?>
<div class="container section">
  <span class="eyebrow">Coaching team</span>
  <h1 class="section-title mt-3">Personal trainers</h1>
  <p class="section-sub mt-2">Only profiles the gym has marked public are listed here. Ask reception to be matched with a coach for your goal.</p>
  <?php if (!$rows): ?>
    <div class="empty-state mt-4"><i class="bi bi-person-badge"></i><strong>Profiles are being prepared</strong><p>Contact reception and we will introduce you to an available coach.</p></div>
  <?php else: ?>
    <div class="row g-4 mt-2">
      <?php foreach ($rows as $t): ?>
        <div class="col-md-6 col-lg-4"><div class="card-eden">
          <?php if (!empty($t['photo_path'])): ?>
            <div class="img-frame mb-3"><img src="<?= e(url('uploads/profiles/' . basename($t['photo_path']))) ?>" alt="Personal trainer <?= e($t['full_name']) ?>" width="400" height="420" loading="lazy"></div>
          <?php else: ?>
            <div class="feature-icon mb-3"><?= e(initials($t['full_name'])) ?></div>
          <?php endif; ?>
          <h3 class="mt-0"><?= e($t['full_name']) ?></h3>
          <p class="small text-muted-eden"><?= (int) $t['experience_years'] ?> years experience</p>
          <?php if ($t['bio']): ?><p><?= e($t['bio']) ?></p><?php endif; ?>
          <?php foreach (array_filter(array_map('trim', explode(',', (string) $t['specializations']))) as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?>
          <?php if ($t['certifications']): ?><p class="small text-muted-eden mt-2">Certifications: <?= e($t['certifications']) ?></p><?php endif; ?>
        </div></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="card-eden mt-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <span class="text-muted-eden">Not sure which coach fits your goal?</span>
    <a href="<?= e(url('contact.php')) ?>" class="btn btn-eden btn-sm">Request a match</a>
  </div>
</div>
<?php public_footer(); ?>
