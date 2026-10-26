<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_head('Contact Us');
public_nav('contact.php');
?>
<div class="container section">
  <span class="eyebrow">Contact</span>
  <h1 class="section-title mt-3">Talk to <?= e(setting('gym_name', 'EDEN WORKOUTS')) ?></h1>
  <p class="section-sub mt-2">Send a message and the team will reply from the enquiry desk. Messages are stored so nothing gets lost.</p>
  <div class="row g-4 mt-2">
    <div class="col-lg-5"><div class="card-eden">
      <h3 class="mt-0">Gym details</h3>
      <p><strong>Address</strong><br><?= e(setting('address', 'To be confirmed')) ?></p>
      <p><strong>Phone</strong><br><?= e(setting('phone', 'To be confirmed')) ?></p>
      <p><strong>Email</strong><br><?= e(setting('email', 'To be confirmed')) ?></p>
      <p><strong>Opening hours</strong><br>Mon–Fri 05:00–22:00 · Sat 06:00–20:00 · Sun 08:00–14:00</p>
    </div></div>
    <div class="col-lg-7"><div class="card-eden">
      <h3 class="mt-0">Send a message</h3>
      <form action="<?= e(url('actions/handle.php?do=contact.submit')) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="contact.php">
        <div class="row g-3">
          <div class="col-sm-6"><div class="field"><label for="name">Full name *</label><input class="input" id="name" name="name" required maxlength="160"></div></div>
          <div class="col-sm-6"><div class="field"><label for="email">Email *</label><input class="input" id="email" name="email" type="email" required maxlength="190"></div></div>
          <div class="col-sm-6"><div class="field"><label for="phone">Phone</label><input class="input" id="phone" name="phone" placeholder="0712 345 678"></div></div>
          <div class="col-sm-6"><div class="field"><label for="subject">Subject *</label><input class="input" id="subject" name="subject" required maxlength="200"></div></div>
          <div class="col-12"><div class="field"><label for="message">Message *</label><textarea class="input" id="message" name="message" rows="6" required minlength="10" maxlength="4000"></textarea></div></div>
          <div class="col-12" aria-hidden="true" style="position:absolute;left:-9999px"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
        </div>
        <button type="submit" class="btn btn-eden">Send message</button>
        <p class="hint">Messages are limited to five per hour to stop spam.</p>
      </form>
    </div></div>
  </div>
</div>
<?php public_footer(); ?>
