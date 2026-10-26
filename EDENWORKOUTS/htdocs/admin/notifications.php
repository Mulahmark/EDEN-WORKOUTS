<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$rows = db_all("SELECT n.*, u.full_name FROM notifications n JOIN users u ON u.id=n.user_id ORDER BY n.created_at DESC LIMIT 60");
dash_start($u, 'Notifications', 'Internal in-app notifications only. Email and SMS providers are not connected, so nothing is sent off-platform.', 'admin/notifications.php');
?>
<div class="panel"><div class="panel-head"><h2>Send an announcement</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=announcement.send')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-6"><div class="field"><label>Title</label><input class="input" name="title" required></div></div>
      <div class="col-md-6"><div class="field"><label>Audience</label><select class="input" name="audience"><option value="all">Everyone with an active account</option><option value="members">Members only</option><option value="staff">Staff only</option></select></div></div>
      <div class="col-12"><div class="field"><label>Message</label><textarea class="input" name="message" rows="3" required></textarea></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Send announcement</button>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2>Notification history (<?= count($rows) ?>)</h2></div><div class="panel-body">
  <?php if (!$rows): ?><?= empty_state('Nothing sent yet', 'System notifications and announcements appear here.') ?>
  <?php else: ?><ul class="plan-benefits"><?php foreach ($rows as $n): ?><li><i class="bi bi-bell"></i><span><strong><?= e($n['title']) ?></strong> → <?= e($n['full_name']) ?><br><span class="small text-muted-eden"><?= e($n['message']) ?> · <?= e(fmt_datetime($n['created_at'])) ?></span></span></li><?php endforeach; ?></ul><?php endif; ?>
</div></div>
<?php dash_end(); ?>
