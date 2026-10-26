<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$s = db_one("SELECT * FROM gym_settings WHERE id = 1") ?: [];
dash_start($u, 'Gym settings', 'Defaults applied across receipts, the public website and reminders.', 'admin/settings.php');
?>
<div class="panel"><div class="panel-head"><h2>Identity, contact &amp; localisation</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=settings.save')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-4"><div class="field"><label>Gym name</label><input class="input" name="gym_name" value="<?= e($s['gym_name'] ?? 'EDEN WORKOUTS') ?>" required></div></div>
      <div class="col-md-8"><div class="field"><label>Tagline</label><input class="input" name="tagline" value="<?= e($s['tagline'] ?? '') ?>"></div></div>
      <div class="col-md-8"><div class="field"><label>Address</label><input class="input" name="address" value="<?= e($s['address'] ?? '') ?>"></div></div>
      <div class="col-md-4"><div class="field"><label>Phone</label><input class="input" name="phone" value="<?= e($s['phone'] ?? '') ?>"></div></div>
      <div class="col-md-4"><div class="field"><label>Email</label><input class="input" name="email" value="<?= e($s['email'] ?? '') ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>Currency</label><select class="input" name="currency"><?php foreach (['KES', 'USD', 'EUR', 'GBP'] as $c): ?><option value="<?= $c ?>" <?= ($s['currency'] ?? 'KES') === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-3"><div class="field"><label>Timezone</label><input class="input" name="timezone" value="<?= e($s['timezone'] ?? 'Africa/Nairobi') ?>"></div></div>
      <div class="col-md-3"><div class="field"><label>First reminder (days)</label><input class="input" type="number" name="reminder_days_7" value="<?= e($s['reminder_days_7'] ?? 7) ?>"></div></div>
      <div class="col-md-3"><div class="field"><label>Second reminder (days)</label><input class="input" type="number" name="reminder_days_30" value="<?= e($s['reminder_days_30'] ?? 30) ?>"></div></div>
      <div class="col-md-3"><div class="field"><label>Tax label</label><input class="input" name="tax_label" value="<?= e($s['tax_label'] ?? '') ?>"></div></div>
      <div class="col-md-3"><div class="field"><label>Tax percent</label><input class="input" type="number" step="0.01" name="tax_percent" value="<?= e($s['tax_percent'] ?? '0') ?>"></div></div>
      <div class="col-12"><div class="field"><label>Receipt footer</label><textarea class="input" name="receipt_footer" rows="2"><?= e($s['receipt_footer'] ?? '') ?></textarea></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Save settings</button>
    <p class="hint">Database credentials and setup keys live in <code>config/database.php</code> — never in this form.</p>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2>Theme tokens</h2></div><div class="panel-body d-flex flex-wrap gap-2">
  <?php foreach ([['Gunmetal', '#2C353D'], ['Charcoal', '#15191D'], ['Surface', '#20262C'], ['Elevated', '#28313A'], ['Sunset orange', '#FF6B35'], ['Warm orange', '#FF914D'], ['Success', '#22C55E'], ['Warning', '#FBBF24'], ['Danger', '#EF4444']] as [$n, $h]): ?>
    <div style="width:120px"><div style="height:32px;border-radius:6px;background:<?= $h ?>;border:1px solid var(--line)"></div><p class="small mt-1 mb-0"><?= e($n) ?><br><span class="text-muted-eden"><?= $h ?></span></p></div>
  <?php endforeach; ?>
</div></div>
<?php dash_end(); ?>
