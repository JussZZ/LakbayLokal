<?php
require __DIR__ . '/includes/bootstrap.php';
$c = cfg('contact');
$pageTitle = 'Contact'; $navActive = 'contact';
include ROOT_PATH . '/includes/header.php';
?>
<section class="page-hero"><div class="wrap">
  <p class="eyebrow">Contact</p>
  <h1 class="display">Get in touch</h1>
  <p class="lead">Questions about a destination or a booking? Reach the team using the details below.</p>
</div></section>
<main class="wrap page-pad two-col" id="main">
  <div class="panel">
    <h2>Contact details</h2>
    
    <dl class="detail-list" style="display:grid;gap:1.2rem;margin:1.2rem 0 0">
      <div><span class="muted">Email</span><br><?= e($c['email']) ?></div>
      <div><span class="muted">Phone</span><br><?= e($c['phone']) ?></div>
      <div><span class="muted">Address</span><br><?= e($c['address']) ?></div>
    </dl>
  </div>
  <div class="prose">
    <h2>Already booked?</h2>
    <p>Log in to see the latest status of your bookings and your booking reference.</p>
    <p><a class="btn btn--amber" href="<?= e(url('user/login.php')) ?>">Log in</a></p>
  </div>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
