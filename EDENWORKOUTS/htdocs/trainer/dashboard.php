<?php
declare(strict_types=1);
require_once __DIR__ . '/_clients_helper.php';
$u = require_role(['trainer', 'admin']);
if (empty($u['trainer_id'])) { err('No trainer profile is linked to this account.'); redirect('login.php'); }
$clients = assigned_clients((int) $u['trainer_id']);
$plans = db_all("SELECT w.*, CONCAT(m.first_name,' ',m.last_name) member_name FROM workout_plans w JOIN members m ON m.id=w.member_id WHERE w.trainer_id = ? ORDER BY w.created_at DESC LIMIT 8", [(int) $u['trainer_id']]);
$upcoming = db_all("SELECT a.*, COALESCE(CONCAT(m.first_name,' ',m.last_name),'Availability block') who FROM training_appointments a LEFT JOIN members m ON m.id=a.member_id WHERE a.trainer_id = ? AND a.scheduled_at >= NOW() AND a.status='scheduled' ORDER BY a.scheduled_at LIMIT 6", [(int) $u['trainer_id']]);
$sessions = (int) db_val("SELECT COUNT(*) FROM workout_sessions s JOIN trainer_assignments a ON a.member_id=s.member_id AND a.trainer_id=? AND a.status='active' WHERE s.performed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [(int) $u['trainer_id']]);
dash_start($u, 'Coach ' . $u['full_name'], 'Personal training dashboard', 'trainer/dashboard.php');
?>
<div class="stat-grid">
  <?= stat_card('Assigned clients', count($clients), '', 'orange') ?><?= stat_card('Plans written', count($plans)) ?>
  <?= stat_card('Upcoming appointments', count($upcoming)) ?><?= stat_card('Client sessions (7 days)', $sessions, '', 'ok') ?>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>My clients</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/clients.php')) ?>">Manage</a></div><div class="panel-body">
    <?php if (!$clients): ?><?= empty_state('No clients assigned', 'An administrator assigns members to you. Only assigned clients are visible.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($clients as $c): ?><li><i class="bi bi-person"></i><span><?= e($c['first_name'] . ' ' . $c['last_name']) ?> <span class="small text-muted-eden"><?= e($c['member_number']) ?></span></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Upcoming appointments</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/appointments.php')) ?>">Diary</a></div><div class="panel-body">
    <?php if (!$upcoming): ?><?= empty_state('Nothing booked', 'Your upcoming sessions appear here.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($upcoming as $a): ?><li><i class="bi bi-calendar-check"></i><span><?= e(fmt_datetime($a['scheduled_at'])) ?> · <?= (int) $a['duration_minutes'] ?> min · <?= e($a['who']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Recent workout plans</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/workouts.php')) ?>">All plans</a></div><div class="panel-body table-wrap">
  <?php if (!$plans): ?><?= empty_state('No plans yet', 'Create a plan for one of your assigned clients.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Client</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($plans as $p): ?><tr><td><?= e($p['name']) ?></td><td class="small"><?= e($p['member_name']) ?></td><td><?= status_badge($p['status']) ?></td><td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/workout.php?id=' . $p['id'])) ?>">Builder</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
