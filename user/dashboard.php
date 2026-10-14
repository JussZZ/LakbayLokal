<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_user();
$pdo = db();

// Summary values (all from SQL aggregation).
$st = $pdo->prepare("SELECT COUNT(*) AS total,
                            COALESCE(SUM(status = 'pending'), 0)   AS pending,
                            COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
                            COALESCE(SUM(status = 'completed'), 0) AS completed
                       FROM bookings WHERE user_id = :u");
$st->execute([':u' => $u['user_id']]);
$stats = $st->fetch();

// Recent bookings: JOIN bookings -> items.
$st = $pdo->prepare("SELECT b.booking_id, b.reference, b.travel_date, b.travelers, b.status, i.name AS destination
                       FROM bookings b JOIN items i ON i.item_id = b.item_id
                      WHERE b.user_id = :u ORDER BY b.created_at DESC, b.booking_id DESC LIMIT 5");
$st->execute([':u' => $u['user_id']]);
$recent = $st->fetchAll();

$pageTitle = 'Dashboard'; $navActive = 'dashboard'; $extraCss = ['dashboard.css'];
include ROOT_PATH . '/includes/header.php';
?>
<main class="wrap page-pad" id="main">
  <div class="dash-head">
    <div><p class="eyebrow">Traveller dashboard</p><h1 class="display">Hello, <?= e(explode(' ', trim($u['full_name']))[0]) ?></h1></div>
    <a class="btn btn--amber arrow" href="<?= e(url('destinations.php')) ?>">Explore destinations</a>
  </div>

<?php if ($u['status'] === 'pending'): ?>
  <div class="notice" role="status"><div><strong>Your account is awaiting approval.</strong><br>You can browse destinations now. Booking opens as soon as an administrator approves your account.</div></div>
<?php elseif ($u['status'] === 'suspended'): ?>
  <div class="notice notice--danger" role="status"><div><strong>Your account is suspended.</strong><br>You can view your existing bookings, but new bookings are unavailable. Please contact LakbayLokal.</div></div>
<?php endif; ?>

  <div class="stat-row">
    <div class="stat"><span class="stat__n"><?= (int) $stats['total'] ?></span><span class="stat__l">Total bookings</span></div>
    <div class="stat"><span class="stat__n"><?= (int) $stats['pending'] ?></span><span class="stat__l">Pending</span></div>
    <div class="stat"><span class="stat__n"><?= (int) $stats['confirmed'] ?></span><span class="stat__l">Confirmed</span></div>
    <div class="stat"><span class="stat__n"><?= (int) $stats['completed'] ?></span><span class="stat__l">Completed</span></div>
  </div>

  <div class="dash-grid">
    <section aria-labelledby="recentH">
      <div class="block-title"><h2 id="recentH">Recent activity</h2><a href="<?= e(url('user/bookings.php')) ?>">All bookings</a></div>
<?php if (!$recent): ?>
      <div class="empty"><h3>No bookings yet</h3><p>When you send a booking request it will show up here.</p><a class="btn btn--amber" href="<?= e(url('destinations.php')) ?>">Find a destination</a></div>
<?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Reference</th><th>Destination</th><th>Travel date</th><th>Travelers</th><th>Status</th></tr></thead>
        <tbody>
<?php foreach ($recent as $b): ?>
          <tr>
            <td><a class="mono" href="<?= e(url('user/booking.php?id=' . (int) $b['booking_id'])) ?>"><?= e($b['reference']) ?></a></td>
            <td><?= e($b['destination']) ?></td>
            <td><?= e(fmt_date($b['travel_date'])) ?></td>
            <td><?= (int) $b['travelers'] ?></td>
            <td><?= badge($b['status']) ?></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table></div>
<?php endif; ?>
    </section>
    <aside aria-labelledby="quickH">
      <div class="block-title"><h2 id="quickH">Quick links</h2></div>
      <div class="quick">
        <a href="<?= e(url('destinations.php')) ?>">Explore destinations</a>
        <a href="<?= e(url('user/bookings.php')) ?>">Manage my bookings</a>
        <a href="<?= e(url('user/profile.php')) ?>">Edit my profile</a>
        <a href="<?= e(url('index.php')) ?>">Back to the landing page</a>
      </div>
    </aside>
  </div>
</main>

