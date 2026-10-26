<?php
declare(strict_types=1);
require_once __DIR__ . '/_clients_helper.php';
$u = require_role(['trainer', 'admin']);
if (empty($u['trainer_id'])) { err('No trainer profile linked.'); redirect('login.php'); }
$clients = assigned_clients((int) $u['trainer_id']);
$plans = db_all("SELECT w.*, CONCAT(m.first_name,' ',m.last_name) member, (SELECT COUNT(*) FROM workout_plan_exercises pe WHERE pe.plan_id=w.id) line_count FROM workout_plans w JOIN members m ON m.id=w.member_id WHERE w.trainer_id = ? ORDER BY w.created_at DESC", [(int) $u['trainer_id']]);
dash_start($u, 'Workout plans', 'Build, duplicate and publish programmes for your assigned clients.', 'trainer/workouts.php');
?>
<div class="panel"><div class="panel-head"><h2>Create a plan</h2></div><div class="panel-body">
  <?php if (!$clients): ?><p class="small text-muted-eden">You need at least one assigned client first.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=workout.save')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-4"><div class="field"><label>Plan name</label><input class="input" name="name" required></div></div>
      <div class="col-md-4"><div class="field"><label>Client</label><select class="input" name="member_id" required><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Start</label><input class="input" type="date" name="start_date" value="<?= e(today()) ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>Status</label><select class="input" name="status"><option value="draft">Draft</option><option value="active">Active</option></select></div></div>
      <div class="col-12"><div class="field"><label>Goals</label><input class="input" name="goals"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Create plan</button></form><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>My plans (<?= count($plans) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$plans): ?><?= empty_state('No plans yet', 'Create a draft plan above, then add exercises.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Client</th><th>Exercises</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($plans as $p): ?><tr><td><?= e($p['name']) ?></td><td class="small"><?= e($p['member']) ?></td><td class="small"><?= (int) $p['line_count'] ?></td><td><?= status_badge($p['status']) ?></td>
      <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/workout.php?id=' . $p['id'])) ?>">Builder</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
