<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pdo = db();
$dist = $pdo->query('SELECT status, COUNT(*) FROM bookings GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$totalB = max(1, array_sum($dist));
$cats = $pdo->query('SELECT category, COUNT(*) FROM items GROUP BY category')->fetchAll(PDO::FETCH_KEY_PAIR);
$totalD = max(1, array_sum($cats));
$top = $pdo->query('SELECT i.name, COUNT(b.booking_id) AS c FROM items i LEFT JOIN bookings b ON b.item_id = i.item_id GROUP BY i.item_id, i.name ORDER BY c DESC, i.name LIMIT 5')->fetchAll();
$maxTop = max(1, (int) ($top[0]['c'] ?? 0));
$logs = $pdo->query('SELECT l.action, l.details, l.created_at, u.username FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id ORDER BY l.log_id DESC LIMIT 40')->fetchAll();

$pageTitle = 'Reports / activity';
$navActive = 'activity';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title">
  <div>
    <p class="eyebrow">Reports</p>
    <h1>Reports &amp; activity</h1>
    <p>Summaries calculated live from the database.</p>
  </div>
</div>
<div class="admin-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
  <section class="panel">
    <h2>Bookings by status</h2>
    <div class="bars">
      <?php foreach (booking_statuses() as $s): $c = (int) ($dist[$s] ?? 0); ?><div>
          <div class="bar__top"><span><?= e(ucfirst($s)) ?></span><span><?= $c ?></span></div>
          <div class="bar__track">
            <div class="bar__fill" style="--w:<?= round($c / $totalB * 100) ?>"></div>
          </div>
        </div><?php endforeach; ?>
    </div>
  </section>
  <section class="panel">
    <h2>Destinations by category</h2>
    <div class="bars">
      <?php foreach (categories() as $k => $l): $c = (int) ($cats[$k] ?? 0); ?><div>
          <div class="bar__top"><span><?= e($l) ?></span><span><?= $c ?></span></div>
          <div class="bar__track">
            <div class="bar__fill" style="--w:<?= round($c / $totalD * 100) ?>"></div>
          </div>
        </div><?php endforeach; ?>
    </div>
  </section>
  <section class="panel">
    <h2>Most booked</h2>
    <?php if (!$top): ?><p class="muted">No destinations yet.</p><?php else: ?><div class="bars">
        <?php foreach ($top as $t): ?><div>
            <div class="bar__top"><span><?= e($t['name']) ?></span><span><?= (int) $t['c'] ?></span></div>
            <div class="bar__track">
              <div class="bar__fill" style="--w:<?= round($t['c'] / $maxTop * 100) ?>"></div>
            </div>
          </div><?php endforeach; ?></div><?php endif; ?>
  </section>
</div>
<div class="block-title" style="margin-top:2.2rem">
  <h2>Recent activity</h2>
</div>
<?php if (!$logs): ?><div class="empty">
    <h3>No activity yet</h3>
  </div><?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>When</th>
          <th>User</th>
          <th>Action</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l): ?><tr>
            <td><?= e(fmt_datetime($l['created_at'])) ?></td>
            <td><?= e($l['username'] ?? 'system') ?></td>
            <td class="mono"><?= e($l['action']) ?></td>
            <td><?= e($l['details']) ?></td>
          </tr><?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>