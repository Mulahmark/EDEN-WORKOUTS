<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$filter = get('status');
$rows = $filter
  ? db_all("SELECT * FROM contact_enquiries WHERE status = ? ORDER BY created_at DESC LIMIT 100", [$filter])
  : db_all("SELECT * FROM contact_enquiries ORDER BY created_at DESC LIMIT 100");
dash_start($u, 'Contact enquiries', 'Messages submitted from the public contact form. Honeypot and per-IP rate limiting are applied.', 'admin/enquiries.php');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach (['' => 'All', 'new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'spam' => 'Spam'] as $k => $l): ?>
    <a class="btn <?= $filter === $k ? 'btn-eden' : 'btn-eden-quiet' ?> btn-sm" href="?status=<?= $k ?>"><?= $l ?></a>
  <?php endforeach; ?>
  <a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=enquiries')) ?>">Export CSV</a>
</div>
<?php if (!$rows): ?><div class="panel panel-body"><?= empty_state('No enquiries', 'Contact form messages appear here with their status.') ?></div>
<?php else: foreach ($rows as $e): ?>
  <div class="panel"><div class="panel-head">
    <h2><?= e($e['subject']) ?> <?= status_badge($e['status']) ?></h2>
    <div class="d-flex flex-wrap gap-1">
      <?php foreach (['new', 'in_progress', 'resolved', 'spam'] as $st): if ($st === $e['status']) continue; ?>
        <form action="<?= e(url('actions/handle.php?do=enquiry.status')) ?>" method="post"><?= csrf_field() ?>
          <input type="hidden" name="enquiry_id" value="<?= (int) $e['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>">
          <button class="btn btn-eden-quiet btn-sm"><?= str_replace('_', ' ', $st) ?></button></form>
      <?php endforeach; ?>
      <a class="btn btn-eden-ghost btn-sm" href="mailto:<?= e($e['email']) ?>">Reply by email</a>
    </div></div>
    <div class="panel-body"><p class="small text-muted-eden mb-2"><?= e($e['name']) ?> · <?= e($e['email']) ?><?= $e['phone'] ? ' · ' . e($e['phone']) : '' ?> · <?= e(fmt_datetime($e['created_at'])) ?></p>
      <p class="small" style="white-space:pre-line"><?= e($e['message']) ?></p></div></div>
<?php endforeach; endif; ?>
<?php dash_end(); ?>
