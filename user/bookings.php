<?php
// Shared by bookings.php (active) and history.php (completed / cancelled).
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_user();
$pdo = db();
$scope = $scope ?? 'active';
$scopeStatuses = $scope === 'history' ? ['completed', 'cancelled'] : ['pending', 'confirmed'];

$q = mb_substr(get_str('q'), 0, 60);
$status = get_str('status');
if (!in_array($status, $scopeStatuses, true)) {
  $status = '';
}
$sorts = ['newest' => ['Newest requests', 'b.created_at DESC, b.booking_id DESC'], 'travel' => ['Travel date', 'b.travel_date ASC']];
$sort = get_str('sort', 'newest');
if (!isset($sorts[$sort])) {
  $sort = 'newest';
}

$where = ['b.user_id = ?'];            // ownership is part of every query
$args = [(int) $u['user_id']];
$use = $status !== '' ? [$status] : $scopeStatuses;
$where[] = 'b.status IN (' . implode(',', array_fill(0, count($use), '?')) . ')';
$args = array_merge($args, $use);
if ($q !== '') {
  $where[] = '(b.reference LIKE ? OR i.name LIKE ?)';
  $args[] = like($q);
  $args[] = like($q);
}
$whereSql = implode(' AND ', $where);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN items i ON i.item_id = b.item_id WHERE $whereSql");
$cnt->execute($args);
$total = (int) $cnt->fetchColumn();
$perPage = (int) cfg('admin_per_page', 10);
[$page, $pages, $offset] = paginate($total, $perPage);

$st = $pdo->prepare("SELECT b.booking_id, b.reference, b.travel_date, b.travelers, b.status, b.created_at, i.name AS destination
                       FROM bookings b JOIN items i ON i.item_id = b.item_id
                      WHERE $whereSql ORDER BY {$sorts[$sort][1]} LIMIT $perPage OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();

$isHistory = $scope === 'history';
$pageTitle = $isHistory ? 'Booking history' : 'My bookings';
$navActive = $isHistory ? 'history' : 'bookings';
$extraCss = ['dashboard.css'];
$params = keep_params(['q', 'status', 'sort']);
include ROOT_PATH . '/includes/header.php';
?>
<main class="wrap page-pad" id="main">
  <div class="dash-head">
    <div>
      <p class="eyebrow"><?= $isHistory ? 'Past trips' : 'Current requests' ?></p>
      <h1 class="display"><?= $isHistory ? 'Booking history' : 'My bookings' ?></h1>
    </div>
    <a class="btn btn--amber arrow" href="<?= e(url('user/create-booking.php')) ?>">New booking</a>
  </div>
  <div class="tabs" role="navigation" aria-label="Booking lists">
    <a href="<?= e(url('user/bookings.php')) ?>" <?= !$isHistory ? ' class="is-active" aria-current="page"' : '' ?>>Active</a>
    <a href="<?= e(url('user/history.php')) ?>" <?= $isHistory ? ' class="is-active" aria-current="page"' : '' ?>>History</a>
  </div>
  <form class="inline-filter" method="get" role="search">
    <div class="field"><label for="q">Search reference or destination</label><input id="q" type="search" name="q" value="<?= e($q) ?>" maxlength="60"></div>
    <div class="field"><label for="status">Status</label>
      <select id="status" name="status">
        <option value="">All</option>
        <?php foreach ($scopeStatuses as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label for="sort">Sort</label>
      <select id="sort" name="sort"><?php foreach ($sorts as $k => [$l]): ?><option value="<?= e($k) ?>" <?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    </div>
    <button class="btn btn--ghost" type="submit" style="grid-column:1/-1;justify-self:start">Apply</button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <?php if ($q !== '' || $status !== ''): ?>
        <h2>No matching bookings</h2>
        <p>Nothing matches your search. Try another keyword or clear the filters.</p>
        <a class="btn btn--ghost" href="<?= e(url($isHistory ? 'user/history.php' : 'user/bookings.php')) ?>">Clear filters</a>
      <?php elseif ($isHistory): ?>
        <h2>No history yet</h2>
        <p>Completed and cancelled bookings will appear here.</p>
      <?php else: ?>
        <h2>No active bookings</h2>
        <p>Browse destinations and send your first booking request.</p>
        <a class="btn btn--amber" href="<?= e(url('destinations.php')) ?>">Explore destinations</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Destination</th>
            <th>Travel date</th>
            <th>Travelers</th>
            <th>Status</th>
            <th>Requested</th>
            <th><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $b): ?>
            <tr>
              <td class="mono"><?= e($b['reference']) ?></td>
              <td><?= e($b['destination']) ?></td>
              <td><?= e(fmt_date($b['travel_date'])) ?></td>
              <td><?= (int) $b['travelers'] ?></td>
              <td><?= badge($b['status']) ?></td>
              <td><?= e(fmt_date($b['created_at'])) ?></td>
              <td><a class="btn btn--ghost btn--sm" href="<?= e(url('user/booking.php?id=' . (int) $b['booking_id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_html($page, $pages, $params) ?>
  <?php endif; ?>
</main>