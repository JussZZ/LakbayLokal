<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pdo = db();

$k = $pdo->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'user')                              AS total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'pending')       AS pending_users,
    (SELECT COUNT(*) FROM bookings)                                               AS total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE status = 'pending')                      AS pending_bookings,
    (SELECT COUNT(*) FROM items WHERE status = 'published')                       AS published_dest,
    (SELECT COUNT(*) FROM items)                                                  AS total_dest,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'recorded') AS recorded_value")->fetch();

$recent = $pdo->query("SELECT b.booking_id, b.reference, b.travel_date, b.travelers, b.status, u.username, i.name AS destination
                         FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN items i ON i.item_id = b.item_id
                        ORDER BY b.created_at DESC, b.booking_id DESC LIMIT 6")->fetchAll();
$dist = $pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalB = max(1, (int) $k['total_bookings']);
$pendingList = $pdo->query("SELECT user_id, username, full_name, created_at FROM users WHERE role = 'user' AND status = 'pending' ORDER BY created_at ASC LIMIT 5")->fetchAll();

$pageTitle = 'Admin dashboard'; $navActive = 'dashboard';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title"><div><p class="eyebrow">Overview</p><h1>Admin dashboard</h1><p>Signed in as <?= e($admin['username']) ?></p></div></div>

<div class="kpi-row">
  <div class="kpi kpi--accent"><span class="kpi__n"><?= (int) $k['total_users'] ?></span><span class="kpi__l">Registered travellers</span></div>
  <div class="kpi kpi--accent"><span class="kpi__n"><?= (int) $k['pending_users'] ?></span><span class="kpi__l">Pending approvals</span></div>
  <div class="kpi"><span class="kpi__n"><?= (int) $k['total_bookings'] ?></span><span class="kpi__l">Total bookings</span></div>
  <div class="kpi"><span class="kpi__n"><?= (int) $k['pending_bookings'] ?></span><span class="kpi__l">Pending bookings</span></div>
  <div class="kpi"><span class="kpi__n"><?= (int) $k['published_dest'] ?><small class="muted" style="font-size:1rem"> / <?= (int) $k['total_dest'] ?></small></span><span class="kpi__l">Published destinations</span></div>
  <div class="kpi"><span class="kpi__n" style="font-size:2rem"><?= e(money($k['recorded_value'])) ?></span><span class="kpi__l">Recorded booking value</span></div>
</div>

<div class="admin-grid">
  <section>
    <div class="block-title"><h2>Latest bookings</h2><a href="<?= e(url('admin/bookings.php')) ?>">Manage</a></div>
<?php if (!$recent): ?>
    <div class="empty"><h3>No bookings yet</h3><p>Requests from travellers will appear here.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Reference</th><th>Traveller</th><th>Destination</th><th>Travel date</th><th>Status</th></tr></thead>
      <tbody>
<?php foreach ($recent as $b): ?>
        <tr><td><a class="mono" href="<?= e(url('admin/bookings.php?id=' . (int) $b['booking_id'])) ?>"><?= e($b['reference']) ?></a></td><td><?= e($b['username']) ?></td><td><?= e($b['destination']) ?></td><td><?= e(fmt_date($b['travel_date'])) ?></td><td><?= badge($b['status']) ?></td></tr>
<?php endforeach; ?>
      </tbody></table></div>
<?php endif; ?>
  </section>
  <section>
    <div class="block-title"><h2>Booking status</h2></div>
    <div class="panel bars">
<?php foreach (booking_statuses() as $s): $c = (int) ($dist[$s] ?? 0); ?>
      <div><div class="bar__top"><span><?= e(ucfirst($s)) ?></span><span><?= $c ?></span></div><div class="bar__track"><div class="bar__fill" style="--w:<?= round($c / $totalB * 100) ?>"></div></div></div>
<?php endforeach; ?>
    </div>
    <div class="block-title" style="margin-top:2rem"><h2>Awaiting approval</h2><a href="<?= e(url('admin/approvals.php')) ?>">Review</a></div>
<?php if (!$pendingList): ?>
    <p class="muted">No accounts are waiting for approval.</p>
<?php else: ?>
    <div class="quick">
<?php foreach ($pendingList as $p): ?>
      <a href="<?= e(url('admin/approvals.php')) ?>"><span><?= e($p['username']) ?> <span class="muted">&middot; <?= e($p['full_name']) ?></span></span></a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>
</div>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>
