<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$rows = db_all("SELECT w.*, CONCAT(m.first_name,' ',m.last_name) member_name, m.member_number, t.full_name trainer, (SELECT COUNT(*) FROM workout_plan_exercises pe WHERE pe.plan_id=w.id) line_count FROM workout_plans w JOIN members m ON m.id=w.member_id LEFT JOIN trainers t ON t.id=w.trainer_id ORDER BY w.created_at DESC");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$trainers = db_all("SELECT id, full_name FROM trainers WHERE employment_status='active' ORDER BY full_name");
dash_start($u, 'Workout plans', 'Every programme written by a coach, with its member, trainer, status and exercise count.', 'admin/workouts.php');
?>
<div class="panel"><div class="panel-head"><h2>Create a plan</h2></div><div class="panel-body">
  <?php if (!$members): ?><p class="small text-muted-eden">Add an active member first.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=workout.save')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><div class="field"><label>Plan name *</label><input class="input" name="name" required placeholder="12-week strength block"></div></div>
      <div class="col-md-4"><div class="field"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' — ' . $m['member_number']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Trainer</label><select class="input" name="trainer_id"><option value="">Unassigned</option><?php foreach ($trainers as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Start</label><input class="input" type="date" name="start_date" value="<?= e(today()) ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>End</label><input class="input" type="date" name="end_date"></div></div>
      <div class="col-md-4"><div class="field"><label>Status</label><select class="input" name="status"><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option><option value="archived">Archived</option></select></div></div>
      <div class="col-md-4"><div class="field"><label>Weekly schedule</label><input class="input" name="weekly_schedule" placeholder="Mon: Push · Wed: Pull · Fri: Legs"></div></div>
      <div class="col-12"><div class="field"><label>Goals</label><textarea class="input" name="goals" rows="2"></textarea></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Create plan</button>
  </form><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Plans (<?= count($rows) ?>)</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=workouts')) ?>">Export CSV</a></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No workout plans yet', 'Create a draft plan, then add exercises day by day.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Member</th><th>Trainer</th><th>Exercises</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $p): ?><tr><td><a href="<?= e(url('admin/workout.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a></td>
      <td class="small"><a href="<?= e(url('admin/member.php?id=' . $p['member_id'])) ?>"><?= e($p['member_name']) ?></a><br><span class="text-muted-eden"><?= e($p['member_number']) ?></span></td>
      <td class="small"><?= e($p['trainer'] ?? '—') ?></td><td class="small"><?= (int) $p['line_count'] ?></td><td><?= status_badge($p['status']) ?></td>
      <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/workout.php?id=' . $p['id'])) ?>">Builder</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
