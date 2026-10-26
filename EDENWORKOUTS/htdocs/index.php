<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$plans    = db_all("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price LIMIT 3");
$trainers = db_all("SELECT * FROM trainers WHERE is_public = 1 AND employment_status = 'active' ORDER BY full_name LIMIT 4");
$trainerCount = (int) db_val("SELECT COUNT(*) FROM trainers WHERE is_public = 1 AND employment_status = 'active'");
$planCount    = (int) db_val("SELECT COUNT(*) FROM membership_plans WHERE is_active = 1");

$features = [
    ['bi-person-arms-up', 'Expert Personal Training', 'One-to-one coaching from trainers who build sessions around your goals.'],
    ['bi-clipboard2-pulse', 'Personalized Workout Plans', 'Structured programmes with sets, reps and rest — assigned to your member account.'],
    ['bi-graph-up-arrow', 'Progress Tracking', 'Log workouts and body measurements, then watch your numbers improve over time.'],
    ['bi-calendar2-check', 'Flexible Memberships', 'From day passes to annual plans — pick what fits your routine and budget.'],
    ['bi-people-fill', 'Fitness Community', 'Train in a supported space where every fitness level is welcome.'],
    ['bi-diagram-3-fill', 'Structured Training', 'Clear programmes and goals so every session has a purpose.'],
];

page_head('Train With Purpose', 'Train with purpose at EDEN WORKOUTS: expert coaches, personalised workout plans, flexible memberships and progress tracking.');
public_nav('index.php');
?>
<style>
/* EDEN WORKOUTS homepage refresh — keeps the existing orange and dark theme */
:root{--eden-orange:#ff7a1a;--eden-orange-light:#ff9a45;--eden-dark:#101114;--eden-panel:#191a1f;--eden-line:rgba(255,255,255,.09);--eden-muted:#b6b7bf}
html{scroll-behavior:smooth}
body{overflow-x:hidden}
.home-refresh{color:#f8f8fa}
.home-refresh .section{position:relative;padding-top:clamp(4rem,7vw,6.5rem);padding-bottom:clamp(4rem,7vw,6.5rem)}
.home-refresh .section-alt{background:linear-gradient(135deg,rgba(255,122,26,.055),rgba(255,255,255,.012));border-top:1px solid rgba(255,255,255,.035);border-bottom:1px solid rgba(255,255,255,.035)}
.home-refresh .eyebrow{display:inline-flex;align-items:center;gap:.55rem;color:var(--eden-orange-light);font-size:.76rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
.home-refresh .eyebrow:before{content:"";display:inline-block;width:24px;height:2px;background:currentColor;border-radius:2px}
.home-refresh .section-title{font-weight:850;letter-spacing:-.045em;line-height:1.08;font-size:clamp(2rem,4vw,3.3rem)}
.home-refresh .section-title span,.home-refresh .hero h1 span{color:var(--eden-orange)}
.home-refresh .section-sub{max-width:640px;color:var(--eden-muted);line-height:1.8}
.home-refresh .hero{position:relative;isolation:isolate;overflow:hidden;padding:clamp(3rem,7vw,6.5rem) 0 4rem;background:radial-gradient(ellipse at 78% 40%,rgba(255,122,26,.16),transparent 38%),linear-gradient(120deg,#101114 15%,#151519 100%)}
.home-refresh .hero:before{content:"";position:absolute;z-index:-1;width:480px;height:480px;right:-180px;top:-230px;border:1px solid rgba(255,122,26,.2);border-radius:50%;box-shadow:0 0 0 55px rgba(255,122,26,.025),0 0 0 110px rgba(255,122,26,.02)}
.home-refresh .hero-inner{display:grid;grid-template-columns:minmax(0,1.02fr) minmax(320px,.98fr);align-items:center;gap:clamp(2rem,5vw,5rem)}
.home-refresh .hero h1{font-size:clamp(3.2rem,6.6vw,6.1rem);font-weight:900;letter-spacing:-.075em;line-height:.99;margin:1.3rem 0 1.5rem;max-width:750px}
.home-refresh .hero .lead{max-width:560px;color:#c5c5cc;font-size:clamp(1rem,1.5vw,1.2rem);line-height:1.8}
.home-refresh .hero-actions{display:flex;flex-wrap:wrap;gap:.85rem;margin-top:1.8rem}
.home-refresh .btn-eden,.home-refresh .btn-eden-ghost{transition:transform .25s ease,box-shadow .25s ease,background .25s ease,border-color .25s ease}
.home-refresh .btn-eden{background:var(--eden-orange);border-color:var(--eden-orange);color:#17120e;font-weight:800;box-shadow:0 9px 28px rgba(255,122,26,.18)}
.home-refresh .btn-eden:hover{background:#ff9343;border-color:#ff9343;color:#17120e;transform:translateY(-3px);box-shadow:0 14px 32px rgba(255,122,26,.3)}
.home-refresh .btn-eden-ghost{border:1px solid rgba(255,255,255,.2);color:#fff;background:rgba(255,255,255,.025);font-weight:700}
.home-refresh .btn-eden-ghost:hover{border-color:var(--eden-orange);background:rgba(255,122,26,.1);color:#fff;transform:translateY(-3px)}
.home-refresh .hero-proof{display:flex;align-items:center;gap:.7rem;margin-top:1.6rem;color:#bdbdc5;font-size:.88rem}
.home-refresh .hero-proof i{color:var(--eden-orange);font-size:1.1rem}
.home-refresh .hero-media{position:relative;min-height:clamp(390px,46vw,600px);border-radius:28px;overflow:hidden;background:#222;box-shadow:0 28px 90px rgba(0,0,0,.42);border:1px solid rgba(255,255,255,.12)}
.home-refresh .hero-slides,.home-refresh .hero-slide{position:absolute;inset:0;width:100%;height:100%}
.home-refresh .hero-slide{object-fit:cover;opacity:0;transform:scale(1.07);transition:opacity 1s ease,transform 6s ease}
.home-refresh .hero-slide.is-active{opacity:1;transform:scale(1)}
.home-refresh .hero-media:after{content:"";position:absolute;inset:35% 0 0;background:linear-gradient(0deg,rgba(7,7,9,.88),transparent);pointer-events:none}
.home-refresh .hero-badge{position:absolute;z-index:2;left:1.25rem;bottom:1.25rem;display:flex;align-items:center;gap:.65rem;padding:.85rem 1rem;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:rgba(16,17,20,.78);backdrop-filter:blur(12px);font-weight:750}
.home-refresh .hero-badge i{color:var(--eden-orange);font-size:1.25rem}
.home-refresh .slide-dots{position:absolute;z-index:3;right:1.25rem;bottom:1.55rem;display:flex;gap:.45rem}
.home-refresh .slide-dot{width:8px;height:8px;padding:0;border:0;border-radius:50%;background:rgba(255,255,255,.45);transition:all .25s;cursor:pointer}
.home-refresh .slide-dot.is-active{width:25px;border-radius:8px;background:var(--eden-orange)}
.home-refresh .metric-row{position:relative;background:#151519;border-top:1px solid var(--eden-line);border-bottom:1px solid var(--eden-line);padding:1.6rem 0}
.home-refresh .metric-row .container{row-gap:1.4rem!important}
.home-refresh .metric-row .container>div{flex:1 1 150px;padding:.3rem 1.2rem;border-left:1px solid rgba(255,255,255,.1)}
.home-refresh .metric-row .container>div:first-child{border-left:0}
.home-refresh .metric-num{font-size:clamp(1.65rem,3vw,2.3rem);font-weight:900;letter-spacing:-.05em;color:var(--eden-orange)}
.home-refresh .metric-label{font-size:.83rem;color:#bdbdc5;margin-top:.15rem}
.home-refresh .card-eden{height:100%;position:relative;overflow:hidden;border:1px solid var(--eden-line);border-radius:20px;background:linear-gradient(145deg,rgba(255,255,255,.045),rgba(255,255,255,.015));padding:1.65rem;transition:transform .3s ease,border-color .3s ease,box-shadow .3s ease}
.home-refresh .card-eden:before{content:"";position:absolute;left:0;top:0;width:0;height:3px;background:var(--eden-orange);transition:width .35s ease}
.home-refresh .card-eden:hover{transform:translateY(-7px);border-color:rgba(255,122,26,.45);box-shadow:0 18px 40px rgba(0,0,0,.22)}
.home-refresh .card-eden:hover:before{width:100%}
.home-refresh .feature-icon{display:grid;place-items:center;width:54px;height:54px;margin-bottom:1.25rem;border-radius:16px;color:var(--eden-orange);background:rgba(255,122,26,.12);border:1px solid rgba(255,122,26,.18);font-size:1.45rem;transition:transform .3s ease,background .3s ease}
.home-refresh .card-eden:hover .feature-icon{transform:rotate(-6deg) scale(1.07);background:rgba(255,122,26,.19)}
.home-refresh .card-eden h3{font-size:1.12rem;font-weight:800;letter-spacing:-.02em}
.home-refresh .card-eden p{color:var(--eden-muted);font-size:.94rem;line-height:1.75;margin-bottom:0}
.home-refresh .img-frame{overflow:hidden;border-radius:22px;border:1px solid rgba(255,255,255,.12);background:#202024}
.home-refresh .img-frame img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .7s cubic-bezier(.2,.7,.2,1)}
.home-refresh .img-frame:hover img{transform:scale(1.045)}
.home-refresh .plan-card{display:flex;flex-direction:column;min-height:100%;padding:1.8rem}
.home-refresh .plan-card.featured{border-color:rgba(255,122,26,.72);background:linear-gradient(150deg,rgba(255,122,26,.12),rgba(255,255,255,.025) 60%);box-shadow:0 18px 55px rgba(255,122,26,.07)}
.home-refresh .plan-badge{position:absolute;top:1rem;right:1rem;border-radius:100px;padding:.35rem .7rem;background:var(--eden-orange);color:#17120e;font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
.home-refresh .plan-price{font-size:clamp(1.6rem,3vw,2.1rem);font-weight:900;letter-spacing:-.045em;color:#fff;margin:1rem 0 .5rem}
.home-refresh .plan-price small{font-size:.8rem;font-weight:500;letter-spacing:0;color:var(--eden-muted)}
.home-refresh .plan-benefits{padding:0;list-style:none;margin:1.2rem 0 1.6rem;display:grid;gap:.75rem}
.home-refresh .plan-benefits li{display:flex;gap:.6rem;color:#d5d5da;font-size:.9rem}
.home-refresh .plan-benefits i{color:var(--eden-orange);margin-top:.15rem}
.home-refresh .plan-card .btn{margin-top:auto}
.home-refresh .chip{display:inline-block;margin:.2rem .2rem .1rem 0;padding:.35rem .6rem;border-radius:100px;background:rgba(255,122,26,.1);border:1px solid rgba(255,122,26,.2);color:#ffc08f;font-size:.72rem}
.home-refresh .trainer-photo{height:245px;overflow:hidden;border-radius:15px;margin-bottom:1.1rem;background:#242428}
.home-refresh .trainer-photo img{width:100%;height:100%;object-fit:cover;transition:transform .6s ease}
.home-refresh .card-eden:hover .trainer-photo img{transform:scale(1.06)}
.home-refresh .cta{position:relative;overflow:hidden;border:1px solid rgba(255,122,26,.3);border-radius:28px;padding:clamp(2.5rem,6vw,5rem) 1.5rem;text-align:center;background:radial-gradient(ellipse at 50% 0,rgba(255,122,26,.2),transparent 55%),linear-gradient(135deg,#1c1b1d,#121216)}
.home-refresh .cta:before,.home-refresh .cta:after{content:"";position:absolute;width:230px;height:230px;border:1px solid rgba(255,122,26,.15);border-radius:50%;pointer-events:none}
.home-refresh .cta:before{left:-100px;top:-100px;box-shadow:0 0 0 28px rgba(255,122,26,.025),0 0 0 56px rgba(255,122,26,.02)}
.home-refresh .cta:after{right:-100px;bottom:-130px;box-shadow:0 0 0 28px rgba(255,122,26,.025),0 0 0 56px rgba(255,122,26,.02)}
.home-refresh .cta>*{position:relative;z-index:1}
.home-refresh .cta>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.13;z-index:0}
.home-refresh .empty-state{border:1px dashed rgba(255,255,255,.17);border-radius:18px;padding:2.5rem 1.5rem;text-align:center;color:var(--eden-muted)}
.home-refresh .empty-state>i{display:block;color:var(--eden-orange);font-size:2rem;margin-bottom:.8rem}
.home-refresh .empty-state strong{display:block;color:#fff;font-size:1.05rem;margin-bottom:.4rem}
.home-refresh .empty-state a{color:var(--eden-orange-light)}
.home-refresh .text-muted-eden{color:var(--eden-muted)!important}
.home-refresh [data-reveal]{opacity:0;transform:translateY(24px);transition:opacity .7s ease,transform .7s cubic-bezier(.2,.7,.2,1)}
.home-refresh [data-reveal].revealed{opacity:1;transform:translateY(0)}
.home-refresh .reveal-delay-1{transition-delay:.08s}.home-refresh .reveal-delay-2{transition-delay:.16s}.home-refresh .reveal-delay-3{transition-delay:.24s}
@media(max-width:767.98px){.home-refresh .hero-inner{grid-template-columns:1fr}.home-refresh .hero{padding-top:3.3rem}.home-refresh .hero h1{font-size:clamp(3rem,14vw,5rem)}.home-refresh .hero-media{min-height:420px}.home-refresh .metric-row .container>div{border-left:0;padding-left:0;flex-basis:40%}.home-refresh .section{padding-top:4rem;padding-bottom:4rem}.home-refresh .trainer-photo{height:220px}}
@media(max-width:420px){.home-refresh .hero-media{min-height:350px}.home-refresh .hero-actions .btn{width:100%;justify-content:center}.home-refresh .card-eden{padding:1.3rem}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.home-refresh *, .home-refresh *:before,.home-refresh *:after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}.home-refresh [data-reveal]{opacity:1;transform:none}}
</style>
<div class="home-refresh">
<section class="hero">
  <div class="container hero-inner">
    <div data-reveal>
      <span class="eyebrow"><i class="bi bi-lightning-charge-fill"></i> Gym &amp; Personal Training</span>
      <h1>Your strength.<br>Your journey.<br><span>Your Eden.</span></h1>
      <p class="lead">Build a stronger you with expert coaching, purposeful workouts, and progress you can actually track. Every level belongs here.</p>
      <div class="hero-actions">
        <a href="<?= e(url('memberships.php')) ?>" class="btn btn-eden">Explore memberships <i class="bi bi-arrow-up-right"></i></a>
        <a href="<?= e(url('register.php')) ?>" class="btn btn-eden-ghost">Become a member</a>
      </div>
      <p class="hero-proof"><i class="bi bi-check-circle-fill"></i> No perfect starting point needed. Just start.</p>
    </div>
    <div class="hero-media" data-reveal>
      <div class="hero-slides" aria-label="A look inside EDEN WORKOUTS">
        <img class="hero-slide is-active" src="<?= e(asset('images/photos/deadlift.jpg')) ?>" alt="Athlete preparing for a deadlift" width="1000" height="1250" fetchpriority="high">
        <img class="hero-slide" src="<?= e(asset('images/photos/dumbbell-rack.jpg')) ?>" alt="Dumbbells ready for strength training" width="1000" height="1250" loading="lazy">
        <img class="hero-slide" src="<?= e(asset('images/photos/group-class.jpg')) ?>" alt="Members training together" width="1000" height="1250" loading="lazy">
        <img class="hero-slide" src="<?= e(asset('images/photos/pexels-pavel-danilyuk-6339356.jpg')) ?>" alt="Focused gym training session" width="1000" height="1250" loading="lazy">
      </div>
      <div class="hero-badge"><i class="bi bi-lightning-charge-fill"></i><span>Show up. Get stronger.</span></div>
      <div class="slide-dots" aria-label="Choose hero image">
        <button class="slide-dot is-active" type="button" aria-label="Show image 1"></button>
        <button class="slide-dot" type="button" aria-label="Show image 2"></button>
        <button class="slide-dot" type="button" aria-label="Show image 3"></button>
        <button class="slide-dot" type="button" aria-label="Show image 4"></button>
      </div>
    </div>
  </div>
</section>

<section class="metric-row" aria-label="EDEN WORKOUTS at a glance">
  <div class="container d-flex flex-wrap gap-4 justify-content-between">
    <div data-reveal><div class="metric-num"><span data-count="<?= $trainerCount ?>"><?= $trainerCount > 0 ? '0' : '—' ?></span></div><div class="metric-label">Active coaches</div></div>
    <div data-reveal class="reveal-delay-1"><div class="metric-num"><span data-count="<?= $planCount ?>"><?= $planCount > 0 ? '0' : '—' ?></span></div><div class="metric-label">Membership options</div></div>
    <div data-reveal class="reveal-delay-2"><div class="metric-num">KES</div><div class="metric-label">Priced in Kenyan shillings</div></div>
    <div data-reveal class="reveal-delay-3"><div class="metric-num"><i class="bi bi-bullseye"></i></div><div class="metric-label">One focus: your progress</div></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="text-center mb-5" data-reveal><span class="eyebrow">Why EDEN</span><h2 class="section-title mt-3">Everything you need to <span>get stronger</span></h2><p class="section-sub mx-auto mt-2">A complete training experience: great coaching, clear plans and honest progress tracking.</p></div>
    <div class="row g-4">
      <?php foreach ($features as $i => [$icon, $title, $text]): ?>
        <div class="col-md-6 col-lg-4" data-reveal><div class="card-eden"><div class="feature-icon"><i class="bi <?= e($icon) ?>"></i></div><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container"><div class="row align-items-center g-5">
    <div class="col-lg-6" data-reveal><div class="img-frame"><img src="<?= e(asset('images/photos/gym-floor.jpg')) ?>" alt="EDEN WORKOUTS training floor with strength machines and free weights" width="1400" height="900" loading="lazy"></div></div>
    <div class="col-lg-6" data-reveal><span class="eyebrow">Our space</span><h2 class="section-title mt-3 mb-3">Built for <span>serious</span> training</h2><p class="text-muted-eden"><?= e(setting('about_text', 'A strength and conditioning floor with racks, platforms, free weights and conditioning equipment.')) ?></p><p class="text-muted-eden">Whether you are learning the basics or chasing a new personal best, make this your space to grow stronger.</p><a href="<?= e(url('about.php')) ?>" class="btn btn-eden-ghost mt-2">More about us <i class="bi bi-arrow-right"></i></a></div>
  </div></div>
</section>

<section class="section">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4" data-reveal><div><span class="eyebrow">Memberships</span><h2 class="section-title mt-3 mb-0">Choose your <span>plan</span></h2></div><a href="<?= e(url('memberships.php')) ?>" class="btn btn-eden-ghost btn-sm">View all plans <i class="bi bi-arrow-right"></i></a></div>
    <?php if (!$plans): ?>
      <div class="empty-state"><i class="bi bi-card-checklist"></i><strong>Plans will appear here soon</strong><p>Membership plans are being published. Please <a href="<?= e(url('contact.php')) ?>">contact us</a> in the meantime.</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($plans as $i => $p): $benefits = array_filter(array_map('trim', preg_split('/\R/', (string) $p['benefits']) ?: [])); ?>
          <div class="col-md-6 col-lg-4" data-reveal><div class="card-eden plan-card <?= $i === 1 ? 'featured' : '' ?>">
            <?php if ($i === 1): ?><span class="plan-badge">Popular</span><?php endif; ?>
            <h3 class="mb-0"><?= e($p['name']) ?></h3><div class="plan-price"><?= e(money($p['price'])) ?> <small>/ <?= e(duration_label((int) $p['duration_value'], $p['duration_unit'])) ?></small></div>
            <?php if ((float) $p['joining_fee'] > 0): ?><p class="small text-muted-eden">+ <?= e(money($p['joining_fee'])) ?> one-off joining fee</p><?php endif; ?>
            <p><?= e($p['description']) ?></p><ul class="plan-benefits"><?php foreach (array_slice($benefits, 0, 4) as $b): ?><li><i class="bi bi-check-circle-fill"></i><span><?= e($b) ?></span></li><?php endforeach; ?></ul>
            <a href="<?= e(url('register.php?plan=' . (int) $p['id'])) ?>" class="btn btn-eden mt-auto">Join with <?= e($p['name']) ?> <i class="bi bi-arrow-up-right"></i></a>
          </div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4" data-reveal><div><span class="eyebrow">Coaching</span><h2 class="section-title mt-3 mb-0">Meet your <span>trainers</span></h2></div><a href="<?= e(url('trainers.php')) ?>" class="btn btn-eden-ghost btn-sm">All trainers <i class="bi bi-arrow-right"></i></a></div>
    <?php if (!$trainers): ?>
      <div class="empty-state"><i class="bi bi-person-badge"></i><strong>Trainer profiles are being set up</strong><p><a href="<?= e(url('contact.php')) ?>">Ask us</a> about personal training and we will match you with a coach.</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($trainers as $t): ?>
          <div class="col-sm-6 col-lg-3" data-reveal><div class="card-eden">
            <?php if (!empty($t['photo_path'])): ?><div class="trainer-photo"><img src="<?= e(url('uploads/profiles/' . basename($t['photo_path']))) ?>" alt="Personal trainer <?= e($t['full_name']) ?>" width="400" height="420" loading="lazy"></div><?php else: ?><div class="feature-icon mb-3"><?= e(initials($t['full_name'])) ?></div><?php endif; ?>
            <h3 class="mt-0"><?= e($t['full_name']) ?></h3><?php foreach (array_slice(array_filter(array_map('trim', explode(',', (string) $t['specializations']))), 0, 3) as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?>
          </div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section"><div class="container"><div class="cta" data-reveal>
  <img src="<?= e(asset('images/photos/trainer-client.jpg')) ?>" alt="" width="1400" height="900" loading="lazy">
  <span class="eyebrow">Ready when you are</span><h2 class="section-title mt-3 mb-3">Your next chapter starts <span>here.</span></h2>
  <p class="mx-auto mb-4 text-muted-eden" style="max-width:34rem">Create your member account, choose a plan and let our team help you take the first step. Your journey does not have to wait.</p>
  <div class="d-flex flex-wrap gap-3 justify-content-center"><a href="<?= e(url('register.php')) ?>" class="btn btn-eden">Join EDEN WORKOUTS <i class="bi bi-arrow-up-right"></i></a><a href="<?= e(url('contact.php')) ?>" class="btn btn-eden-ghost">Talk to us</a></div>
</div></div></section>
</div>
<script>
(() => {
  const root = document.querySelector('.home-refresh');
  if (!root) return;
  // Reveal elements as they enter the viewport; keep content visible on older browsers.
  const revealItems = root.querySelectorAll('[data-reveal], .section .col-md-6, .section .col-lg-4, .section .col-sm-6');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -35px 0px' });
    revealItems.forEach(item => { item.setAttribute('data-reveal', ''); revealObserver.observe(item); });
  } else {
    revealItems.forEach(item => item.classList.add('revealed'));
  }

  // Hero image slideshow with manual dots, pause on hover and reduced-motion support.
  const slides = [...root.querySelectorAll('.hero-slide')];
  const dots = [...root.querySelectorAll('.slide-dot')];
  const hero = root.querySelector('.hero-media');
  let current = 0, timer = null;
  const showSlide = index => {
    current = index;
    slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current));
    dots.forEach((dot, i) => { dot.classList.toggle('is-active', i === current); dot.setAttribute('aria-pressed', i === current ? 'true' : 'false'); });
  };
  const stop = () => { if (timer) window.clearInterval(timer); timer = null; };
  const start = () => {
    stop();
    if (slides.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      timer = window.setInterval(() => showSlide((current + 1) % slides.length), 4800);
    }
  };
  dots.forEach((dot, i) => dot.addEventListener('click', () => { showSlide(i); start(); }));
  if (hero) { hero.addEventListener('mouseenter', stop); hero.addEventListener('mouseleave', start); }
  document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
  start();

  // Count-up figures only when visible.
  const counters = root.querySelectorAll('[data-count]');
  const countUp = el => {
    const target = Number.parseInt(el.dataset.count || '0', 10);
    if (!Number.isFinite(target) || target <= 0 || el.dataset.counted) return;
    el.dataset.counted = '1';
    const duration = 850, startTime = performance.now();
    const tick = now => {
      const progress = Math.min((now - startTime) / duration, 1);
      el.textContent = String(Math.round(target * (1 - Math.pow(1 - progress, 3))));
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  if ('IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { countUp(entry.target); counterObserver.unobserve(entry.target); }
    }), { threshold: .5 });
    counters.forEach(counter => counterObserver.observe(counter));
  } else counters.forEach(countUp);
})();
</script>
<?php public_footer(); ?>