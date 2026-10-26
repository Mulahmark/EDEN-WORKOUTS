<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$rows = db_all("SELECT t.*, (SELECT COUNT(*) FROM trainer_assignments a WHERE a.trainer_id=t.id AND a.status='active') clients FROM trainers t ORDER BY t.full_name");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$avail = db_all("SELECT a.*, t.full_name FROM trainer_availability a JOIN trainers t ON t.id=a.trainer_id ORDER BY t.full_name, a.day_of_week");
$days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
dash_start($u, 'Personal trainers', 'Trainer profiles, public visibility, client assignments and sign-in accounts.', 'admin/trainers.php');
?>
<div class="panel"><div class="panel-head"><h2>Register a trainer</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=trainers')) ?>">Export CSV</a></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=trainer.save')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><div class="field"><label>Full name *</label><input class="input" name="full_name" required></div></div>
      <div class="col-md-4"><div class="field"><label>Email (sign-in) *</label><input class="input" type="email" name="email" required></div></div>
      <div class="col-md-4"><div class="field"><label>Phone *</label><input class="input" name="phone" required></div></div>
      <div class="col-md-2"><div class="field"><label>Experience (years)</label><input class="input" type="number" min="0" name="experience_years" value="0"></div></div>
      <div class="col-md-4"><div class="field"><label>Specializations</label><input class="input" name="specializations" placeholder="Strength, fat loss"></div></div>
      <div class="col-md-3"><div class="field"><label>Employment</label><select class="input" name="employment_status"><option value="active">Active</option><option value="on_leave">On leave</option><option value="inactive">Inactive</option></select></div></div>
      <div class="col-md-3"><div class="field"><label>Initial password *</label><input class="input" type="password" name="password" required minlength="8"></div></div>
      <div class="col-md-6"><div class="field"><label>Certifications</label><input class="input" name="certifications"></div></div>
      <div class="col-md-6"><div class="field"><label>Availability notes</label><input class="input" name="availability_notes"></div></div>
      <div class="col-12"><div class="field"><label>Public biography</label><textarea class="input" name="bio" rows="2"></textarea></div></div>
      <div class="col-12"><label class="small text-muted-eden"><input type="checkbox" name="is_public" value="1" checked> Show this profile on the public trainers page</label></div>
    </div>
    <button class="btn btn-eden mt-2" type="submit">Create trainer</button>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2>Assign a client</h2></div><div class="panel-body">
  <?php if (!$rows || !$members): ?><p class="small text-muted-eden">Add trainers and active members first.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=assignment.create')) ?>" method="post" class="d-flex flex-wrap gap-2 align-items-end"><?= csrf_field() ?>
    <div class="field mb-0"><label>Trainer</label><select class="input" name="trainer_id" required><?php foreach ($rows as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></div>
    <div class="field mb-0"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' — ' . $m['member_number']) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-eden btn-sm">Assign</button>
  </form><p class="hint">Any previous active assignment for that member is closed and kept in history.</p><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Trainer roster (<?= count($rows) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No trainers yet', 'Register a trainer to assign clients and build workout plans.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Trainer</th><th>Contact</th><th>Clients</th><th>Public</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $t): ?><tr><td><strong><?= e($t['full_name']) ?></strong><br><span class="small text-muted-eden"><?= e($t['specializations'] ?? '') ?></span></td>
      <td class="small"><?= e($t['email']) ?><br><?= e($t['phone']) ?></td><td class="small"><?= (int) $t['clients'] ?></td>
      <td><?= status_badge((int) $t['is_public'] ? 'active' : 'inactive') ?></td><td><?= status_badge($t['employment_status']) ?></td>
      <td><form action="<?= e(url('actions/handle.php?do=trainer.status')) ?>" method="post"><?= csrf_field() ?>
        <input type="hidden" name="trainer_id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="employment_status" value="<?= $t['employment_status'] === 'active' ? 'inactive' : 'active' ?>">
        <button class="btn btn-eden-ghost btn-sm"><?= $t['employment_status'] === 'active' ? 'Deactivate' : 'Reactivate' ?></button></form></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Weekly availability</h2></div><div class="panel-body">
  <?php if ($rows): ?><form action="<?= e(url('actions/handle.php?do=availability.save')) ?>" method="post" class="d-flex flex-wrap gap-2 align-items-end mb-3"><?= csrf_field() ?>
    <div class="field mb-0"><label>Trainer</label><select class="input" name="trainer_id"><?php foreach ($rows as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></div>
    <div class="field mb-0"><label>Day</label><select class="input" name="day_of_week"><?php foreach ($days as $i => $d): ?><option value="<?= $i ?>"><?= $d ?></option><?php endforeach; ?></select></div>
    <div class="field mb-0"><label>From</label><input class="input" type="time" name="start_time" value="06:00" required></div>
    <div class="field mb-0"><label>To</label><input class="input" type="time" name="end_time" value="20:00" required></div>
    <button class="btn btn-eden-ghost btn-sm">Add slot</button></form><?php endif; ?>
  <?php if (!$avail): ?><p class="small text-muted-eden">No availability recorded yet.</p>
  <?php else: ?><ul class="plan-benefits"><?php foreach ($avail as $a): ?><li><i class="bi bi-clock"></i><span><?= e($a['full_name']) ?> · <?= e($days[(int) $a['day_of_week']]) ?> <?= e(substr($a['start_time'], 0, 5)) ?>–<?= e(substr($a['end_time'], 0, 5)) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
</div></div>
<?php dash_end(); ?>
