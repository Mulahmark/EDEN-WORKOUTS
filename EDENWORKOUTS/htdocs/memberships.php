<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_head('Membership Plans');
public_nav('memberships.php');
$plans = db_all("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price");
?>
<div class="container section">
  <span class="eyebrow">Memberships</span>
  <h1 class="section-title mt-3">Choose how you train</h1>
  <p class="section-sub mt-2">Prices are maintained by the gym administrator and shown in <?= e(setting('currency', 'KES')) ?>. Joining fees and discounts are confirmed at reception.</p>
  <?php if (!$plans): ?>
    <div class="empty-state mt-4"><i class="bi bi-card-checklist"></i><strong>No plans published</strong><p>Please call <?= e(setting('phone', 'reception')) ?> for the current price list.</p></div>
  <?php else: ?>
    <div class="row g-4 mt-2">
      <?php foreach ($plans as $p): $benefits = array_filter(array_map('trim', preg_split('/\R/', (string) $p['benefits']) ?: [])); ?>
        <div class="col-md-6 col-lg-4"><div class="card-eden plan-card">
          <h3 class="mb-0"><?= e($p['name']) ?></h3>
          <p class="small text-muted-eden mb-0"><?= e(duration_label((int) $p['duration_value'], $p['duration_unit'])) ?></p>
          <div class="plan-price"><?= e(money($p['price'])) ?></div>
          <?php if ((float) $p['joining_fee'] > 0): ?><p class="small text-muted-eden">+ <?= e(money($p['joining_fee'])) ?> one-off joining fee</p><?php endif; ?>
          <p><?= e($p['description']) ?></p>
          <?php if ($benefits): ?>
            <ul class="plan-benefits"><?php foreach ($benefits as $b): ?><li><i class="bi bi-check-circle-fill"></i><span><?= e($b) ?></span></li><?php endforeach; ?></ul>
          <?php endif; ?>
          <?php if ((string) $p['access_notes'] !== ''): ?><p class="small text-muted-eden">Access: <?= e($p['access_notes']) ?></p><?php endif; ?>
          <a href="<?= e(url('register.php?plan=' . (int) $p['id'])) ?>" class="btn btn-eden mt-auto">Join with <?= e($p['name']) ?></a>
        </div></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <p class="hint mt-4">Membership periods run from your start date; the end date on your subscription is the last day your access is valid. Payments are recorded at reception and a printable receipt is issued for every payment.</p>
</div>
<?php public_footer(); ?>
