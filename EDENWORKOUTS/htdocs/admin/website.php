<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$s = db_one("SELECT * FROM gym_settings WHERE id = 1") ?: [];
dash_start($u, 'Public website content', 'Editable copy and social links. Membership plans and trainer profiles come from their own modules.', 'admin/website.php');
?>
<div class="panel"><div class="panel-head"><h2>Content</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('index.php')) ?>">View home</a></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=website.save')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-6"><div class="field"><label>About text</label><textarea class="input" name="about_text" rows="5"><?= e($s['about_text'] ?? '') ?></textarea></div></div>
      <div class="col-md-6"><div class="field"><label>Mission statement</label><textarea class="input" name="mission_text" rows="5"><?= e($s['mission_text'] ?? '') ?></textarea></div></div>
      <div class="col-md-4"><div class="field"><label>Facebook URL</label><input class="input" name="facebook_url" value="<?= e($s['facebook_url'] ?? '') ?>"></div></div>
      <div class="col-md-4"><div class="field"><label>Instagram URL</label><input class="input" name="instagram_url" value="<?= e($s['instagram_url'] ?? '') ?>"></div></div>
      <div class="col-md-4"><div class="field"><label>X URL</label><input class="input" name="x_url" value="<?= e($s['x_url'] ?? '') ?>"></div></div>
      <div class="col-md-4"><div class="field"><label>TikTok URL</label><input class="input" name="tiktok_url" value="<?= e($s['tiktok_url'] ?? '') ?>"></div></div>
      <div class="col-md-4"><div class="field"><label>WhatsApp number</label><input class="input" name="whatsapp_number" value="<?= e($s['whatsapp_number'] ?? '') ?>"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Save website content</button>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2>Brand assets</h2></div><div class="panel-body"><p class="small text-muted-eden">Logo, icon and favicon live in <code>assets/images/</code>. Replace the SVG files to rebrand — no template changes needed. Photography is in <code>assets/images/photos/</code>.</p></div></div>
<?php dash_end(); ?>
