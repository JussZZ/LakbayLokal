<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pdo = db();

$q = mb_substr(get_str('q'), 0, 60);
$status = get_str('status');
if (!in_array($status, ['recorded', 'voided'], true)) {
  $status = '';
}
$from = get_str('from');
if ($from !== '' && !valid_date($from)) {
  $from = '';
}
$to = get_str('to');
if ($to !== '' && !valid_date($to)) {
  $to = '';
}
$sorts = ['newest' => ['Newest', 't.transaction_date DESC, t.transaction_id DESC'], 'amount' => ['Amount (high first)', 't.amount DESC'], 'oldest' => ['Oldest', 't.transaction_date ASC']];
$sort = get_str('sort', 'newest');
if (!isset($sorts[$sort])) {
  $sort = 'newest';
}
$where = ['1=1'];
$args = [];
if ($q !== '') {
  $where[] = '(b.reference LIKE ? OR u.username LIKE ? OR i.name LIKE ?)';
  array_push($args, like($q), like($q), like($q));
}
if ($status !== '') {
  $where[] = 't.status = ?';
  $args[] = $status;
}
if ($from !== '') {
  $where[] = 't.transaction_date >= ?';
  $args[] = $from . ' 00:00:00';
}
if ($to !== '') {
  $where[] = 't.transaction_date < ?';
  $args[] = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
}
$whereSql = implode(' AND ', $where);
// Real JOIN across transactions, users, items and bookings.
$base = "FROM transactions t JOIN users u ON u.user_id = t.user_id JOIN items i ON i.item_id = t.item_id LEFT JOIN bookings b ON b.booking_id = t.booking_id WHERE $whereSql";

// CSV export (bonus): same filters, formula-injection safe.
if (get_str('export') === '1') {
  $st = $pdo->prepare("SELECT t.transaction_id, t.transaction_date, b.reference, u.username, i.name AS destination, t.quantity, t.amount, t.currency, t.status $base ORDER BY {$sorts[$sort][1]}");
  $st->execute($args);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="lakbaylokal-transactions-' . date('Ymd') . '.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Transaction ID', 'Date', 'Booking reference', 'Traveller', 'Destination', 'Quantity', 'Amount', 'Currency', 'Status']);
  while ($r = $st->fetch()) {
    fputcsv($out, array_map(fn($c) => (is_string($c) && $c !== '' && strpos('=+-@', $c[0]) !== false) ? "'" . $c : $c, array_values($r)));
  }
  fclose($out);
  exit;
}

$c = $pdo->prepare("SELECT COUNT(*) $base");
$c->execute($args);
$total = (int) $c->fetchColumn();
$perPage = (int) cfg('admin_per_page', 10);
[$page, $pages, $offset] = paginate($total, $perPage);
$st = $pdo->prepare("SELECT t.transaction_id, t.transaction_date, t.quantity, t.amount, t.currency, t.status, b.reference, b.booking_id, u.username, i.name AS destination $base ORDER BY {$sorts[$sort][1]} LIMIT $perPage OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();
$sum = $pdo->query("SELECT COALESCE(SUM(CASE WHEN status='recorded' THEN amount END),0) AS v, SUM(status='recorded') AS r, SUM(status='voided') AS x FROM transactions")->fetch();
$params = keep_params(['q', 'status', 'from', 'to', 'sort']);
$filtered = $q !== '' || $status !== '' || $from !== '' || $to !== '';

$pageTitle = 'Transaction management';
$navActive = 'transactions';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title">
  <div>
    <p class="eyebrow">Transactions</p>
    <h1>Transaction management</h1>
    <p>Created automatically when a booking is confirmed. No payment gateway is connected, so these record the amount due, not money received.</p>
  </div>
  <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/transactions.php?' . http_build_query($params + ['export' => 1]))) ?>">Export CSV</a>
</div>
<div class="kpi-row" style="grid-template-columns:repeat(3,1fr)">
  <div class="kpi kpi--accent"><span class="kpi__n" style="font-size:2rem"><?= e(money($sum['v'])) ?></span><span class="kpi__l">Recorded value</span></div>
  <div class="kpi"><span class="kpi__n"><?= (int) $sum['r'] ?></span><span class="kpi__l">Recorded</span></div>
  <div class="kpi"><span class="kpi__n"><?= (int) $sum['x'] ?></span><span class="kpi__l">Voided</span></div>
</div>
<form class="admin-filter" method="get" role="search">
  <div class="field"><label for="q">Search reference, user, destination</label><input id="q" type="search" name="q" value="<?= e($q) ?>" maxlength="60"></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status">
      <option value="">All</option>
      <option value="recorded" <?= $status === 'recorded' ? ' selected' : '' ?>>Recorded</option>
      <option value="voided" <?= $status === 'voided' ? ' selected' : '' ?>>Voided</option>
    </select></div>
  <div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="<?= e($to) ?>"></div>
  <div class="field"><label for="sort">Sort</label><select id="sort" name="sort"><?php foreach ($sorts as $k => [$l]): ?><option value="<?= e($k) ?>" <?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--amber btn--sm" type="submit">Apply</button>
</form>
<?php if (!$rows): ?>
  <div class="empty">
    <h2><?= $filtered ? 'No matching transactions' : 'No transactions yet' ?></h2>
    <p><?= $filtered ? 'Try different filters.' : 'A transaction is recorded each time a booking is confirmed.' ?></p>
  </div>
<?php else: ?>
  <p class="muted"><?= $total ?> transaction<?= $total === 1 ? '' : 's' ?></p>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Date</th>
          <th>Booking</th>
          <th>Traveller</th>
          <th>Destination</th>
          <th class="num">Qty</th>
          <th class="num">Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>#<?= (int) $r['transaction_id'] ?></td>
            <td><?= e(fmt_datetime($r['transaction_date'])) ?></td>
            <td><?php if ($r['booking_id']): ?><a class="mono" href="<?= e(url('admin/bookings.php?id=' . (int) $r['booking_id'])) ?>"><?= e($r['reference']) ?></a><?php else: ?>—<?php endif; ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['destination']) ?></td>
            <td class="num"><?= (int) $r['quantity'] ?></td>
            <td class="num"><?= e(money($r['amount'])) ?></td>
            <td><?= badge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($page, $pages, $params) ?>
<?php endif; ?>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>