<?php
declare(strict_types=1);
require_once __DIR__ . '/_clients_helper.php';
$u = require_role(['trainer', 'admin']);
if (empty($u['trainer_id'])) { err('No trainer profile linked.'); redirect('login.php'); }
$clients = assigned_clients((int) $u['trainer_id']);
dash_start($u, 'My clients', 'Only members assigned to you are listed. Records for other members are blocked server-side.', 'trainer/clients.php');
?>
<div class="panel"><div class="panel-head"><h2>Assigned clients (<?= count($clients) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$clients): ?><?= empty_state('No clients assigned', 'An administrator assigns members to trainers from the Trainers screen.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Client</th><th>Member no</th><th>Assigned</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($clients as $c): ?><tr><td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td><td class="small"><?= e($c['member_number']) ?></td>
      <td class="small"><?= e(fmt_date($c['assigned_at'])) ?></td><td><?= status_badge($c['status']) ?></td>
      <td class="d-flex gap-1"><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/progress.php?member_id=' . $c['id'])) ?>">Progress</a>
      <a class="btn btn-eden-ghost btn-sm" href="<?= e(url('trainer/sessions.php?member_id=' . $c['id'])) ?>">Log session</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
