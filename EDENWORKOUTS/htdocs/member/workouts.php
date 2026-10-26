<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$plans = db_all("SELECT w.*, COALESCE(t.full_name,'—') trainer FROM workout_plans w LEFT JOIN trainers t ON t.id=w.trainer_id WHERE w.member_id=? ORDER BY w.created_at DESC", [$mid]);
$active = null; foreach ($plans as $p) if ($p['status'] === 'active') { $active = $p; break; }
if (!$active && $plans) $active = $plans[0];
$lines = $active ? db_all("SELECT pe.*, e.name, e.muscle_group FROM workout_plan_exercises pe JOIN exercises e ON e.id=pe.exercise_id WHERE pe.plan_id=? ORDER BY pe.training_day, pe.order_index", [$active['id']]) : [];
$days = []; foreach ($lines as $l) $days[$l['training_day']][] = $l;
dash_start($u, 'My workout plans', 'Programmes your coach wrote for you. Only your own plans are visible.', 'member/workouts.php');
?>
<?php if (!$plans): ?>
  <div class="panel panel-body"><?= empty_state('No plans assigned', 'Your coach publishes your programme here. Ask at reception to be assigned a trainer.') ?></div>
<?php else: ?>
  <div class="panel"><div class="panel-head"><h2><?= e($active['name']) ?> <?= status_badge($active['status']) ?></h2><span class="small text-muted-eden">Coach: <?= e($active['trainer']) ?></span></div><div class="panel-body">
    <?php if ($active['description']): ?><p class="small text-muted-eden"><?= e($active['description']) ?></p><?php endif; ?>
    <?php if ($active['weekly_schedule']): ?><p class="small"><strong>Schedule:</strong> <?= e($active['weekly_schedule']) ?></p><?php endif; ?>
    <?php if (!$days): ?><?= empty_state('No exercises yet', 'Your coach is still building this plan.') ?>
    <?php else: foreach ($days as $day => $items): ?>
      <h3 class="mt-3" style="font-size:.8rem;letter-spacing:.14em;text-transform:uppercase;color:var(--orange)"><?= e($day) ?></h3>
      <div class="table-wrap"><table class="data"><thead><tr><th>Exercise</th><th>Sets × Reps</th><th>Target</th><th>Rest</th><th>Coach notes</th></tr></thead><tbody>
        <?php foreach ($items as $l): ?><tr><td><?= e($l['name']) ?><br><span class="small text-muted-eden"><?= e($l['muscle_group'] ?? '') ?></span></td>
          <td class="small"><?= (int) $l['sets'] ?> × <?= e($l['reps']) ?></td><td class="small"><?= $l['target_weight'] ? e($l['target_weight'] . ' kg') : '—' ?></td>
          <td class="small"><?= (int) $l['rest_seconds'] ?>s</td><td class="small"><?= e($l['instructions'] ?? '—') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endforeach; endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>All plans</h2></div><div class="panel-body table-wrap"><table class="data"><thead><tr><th>Plan</th><th>Coach</th><th>Created</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($plans as $p): ?><tr><td class="small"><?= e($p['name']) ?></td><td class="small"><?= e($p['trainer']) ?></td><td class="small"><?= e(fmt_date($p['created_at'])) ?></td><td><?= status_badge($p['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
<?php endif; ?>
<?php dash_end(); ?>
