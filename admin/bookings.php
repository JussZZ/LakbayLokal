<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pdo = db();

/** Validated status change. Confirming reserves slots + records a transaction; cancelling a confirmed booking reverses both. */
function change_booking_status(PDO $pdo, array $admin, int $id, string $new, string $note): array
{
    if (!in_array($new, booking_statuses(), true)) { return [false, 'Unknown status.']; }
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM bookings WHERE booking_id = :id FOR UPDATE'); $st->execute([':id' => $id]); $b = $st->fetch();
        if (!$b) { $pdo->rollBack(); return [false, 'Booking not found.']; }
        $old = $b['status'];
        if (!in_array($new, booking_transitions()[$old] ?? [], true)) { $pdo->rollBack(); return [false, 'A ' . $old . ' booking cannot be changed to ' . $new . '.']; }
        if ($new === 'completed' && $b['travel_date'] > date('Y-m-d')) { $pdo->rollBack(); return [false, 'A booking cannot be completed before its travel date.']; }
        $st = $pdo->prepare('SELECT price_per_person FROM items WHERE item_id = :i FOR UPDATE'); $st->execute([':i' => $b['item_id']]); $item = $st->fetch();

        if ($old === 'pending' && $new === 'confirmed') {
            $up = $pdo->prepare('UPDATE items SET quantity = quantity - :n WHERE item_id = :i AND quantity >= :n2');
            $up->execute([':n' => $b['travelers'], ':n2' => $b['travelers'], ':i' => $b['item_id']]);
            if ($up->rowCount() !== 1) { $pdo->rollBack(); return [false, 'Not enough slots remaining for this destination.']; }
            $tx = $pdo->prepare("INSERT INTO transactions (item_id, user_id, quantity, booking_id, amount, currency, status, note) VALUES (:i, :u, :q, :b, :a, :c, 'recorded', :note)");
            $tx->execute([':i' => $b['item_id'], ':u' => $b['user_id'], ':q' => $b['travelers'], ':b' => $id, ':a' => (float) $item['price_per_person'] * (int) $b['travelers'],
                          ':c' => cfg('currency_code', 'PHP'), ':note' => 'Booking confirmed. Amount due; no online payment processed.']);
        }
        if ($old === 'confirmed' && $new === 'cancelled') {
            $pdo->prepare('UPDATE items SET quantity = quantity + :n WHERE item_id = :i')->execute([':n' => $b['travelers'], ':i' => $b['item_id']]);
            $pdo->prepare("UPDATE transactions SET status = 'voided' WHERE booking_id = :b")->execute([':b' => $id]);
        }
        $pdo->prepare('UPDATE bookings SET status = :s WHERE booking_id = :id')->execute([':s' => $new, ':id' => $id]);
        $pdo->prepare('INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, note) VALUES (:b, :o, :n, :u, :note)')
            ->execute([':b' => $id, ':o' => $old, ':n' => $new, ':u' => $admin['user_id'], ':note' => $note !== '' ? $note : 'Updated by administrator']);
        log_activity((int) $admin['user_id'], 'booking.' . $new, $b['reference'] . ': ' . $old . ' -> ' . $new);
        $pdo->commit();
        return [true, 'Booking ' . $b['reference'] . ' is now ' . $new . '.'];
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

if (is_post()) {
    csrf_verify();
    $id = post_int('id');
    $note = mb_substr(post_str('note'), 0, 255);
    [$ok, $msg] = change_booking_status($pdo, $admin, $id, post_str('new_status'), $note);
    flash($ok ? 'success' : 'error', $msg);
    redirect('admin/bookings.php?id=' . $id);
}

// ---------- DETAIL VIEW ----------
$viewId = get_int('id');
if ($viewId) {
    $st = $pdo->prepare("SELECT b.*, u.username, u.full_name, u.email AS user_email, i.name AS destination, i.location, i.price_per_person,
                                t.transaction_id, t.amount, t.currency, t.status AS tx_status
                           FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN items i ON i.item_id = b.item_id
                           LEFT JOIN transactions t ON t.booking_id = b.booking_id WHERE b.booking_id = :id");
    $st->execute([':id' => $viewId]); $b = $st->fetch();
    if (!$b) { flash('error', 'Booking not found.'); redirect('admin/bookings.php'); }
    $st = $pdo->prepare('SELECT h.*, u.username FROM booking_status_history h LEFT JOIN users u ON u.user_id = h.changed_by WHERE h.booking_id = :b ORDER BY h.history_id');
    $st->execute([':b' => $viewId]); $hist = $st->fetchAll();
    $next = booking_transitions()[$b['status']] ?? [];
    $pageTitle = 'Booking ' . $b['reference']; $navActive = 'bookings';
    include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title"><div><p class="eyebrow">Booking</p><h1 class="mono" style="font-family:var(--display)"><?= e($b['reference']) ?></h1></div>
  <div><?= badge($b['status']) ?> <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/bookings.php')) ?>">&larr; All bookings</a></div></div>
<div class="admin-grid">
  <section class="panel">
    <h2>Details</h2>
    <div class="detail-list" style="display:grid;grid-template-columns:repeat(2,1fr);gap:1.2rem 2rem;margin-top:1rem">
      <div><span class="muted">Traveller</span><br><?= e($b['full_name']) ?> (<?= e($b['username']) ?>)</div>
      <div><span class="muted">Destination</span><br><?= e($b['destination']) ?>, <?= e($b['location']) ?></div>
      <div><span class="muted">Travel date</span><br><?= e(fmt_date($b['travel_date'], 'D, M j, Y')) ?></div>
      <div><span class="muted">Travelers</span><br><?= (int) $b['travelers'] ?></div>
      <div><span class="muted">Contact</span><br><?= e($b['contact_name']) ?><br><?= e($b['contact_email']) ?><br><?= e($b['contact_phone'] ?: '—') ?></div>
      <div><span class="muted">Requested</span><br><?= e(fmt_datetime($b['created_at'])) ?></div>
      <div><span class="muted">Estimated value</span><br><?= e(money($b['price_per_person'] * $b['travelers'])) ?></div>
      <div><span class="muted">Transaction</span><br><?= $b['transaction_id'] ? '#' . (int) $b['transaction_id'] . ' ' . badge($b['tx_status']) : 'None recorded' ?></div>
    </div>
<?php if ($b['special_requests']): ?><p style="margin-top:1.2rem"><span class="muted">Special requests</span><br><?= nl2br(e($b['special_requests'])) ?></p><?php endif; ?>
  </section>
  <section>
    <div class="panel">
      <h2>Update status</h2>
<?php if (!$next): ?>
      <p class="muted">This booking is <?= e($b['status']) ?>, so its status is final.</p>
<?php else: ?>
      <form method="post" data-confirm="Change the status of <?= e($b['reference']) ?>?" data-confirm-label="Update status">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['booking_id'] ?>">
        <div class="field"><label for="new_status">New status</label><select id="new_status" name="new_status">
<?php foreach ($next as $s): ?><option value="<?= e($s) ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select>
          <p class="hint">Confirming reserves the travelers' slots and records a transaction. Cancelling a confirmed booking releases them.</p></div>
        <div class="field"><label for="note">Note (optional)</label><input id="note" type="text" name="note" maxlength="255"></div>
        <button class="btn btn--amber btn--sm" type="submit">Update status</button>
      </form>
<?php endif; ?>
    </div>
    <div class="panel" style="margin-top:1.2rem"><h2>History</h2>
      <ol class="timeline">
<?php foreach ($hist as $h): ?><li><strong><?= e(ucfirst($h['new_status'])) ?></strong><?= $h['note'] ? ' &mdash; ' . e($h['note']) : '' ?><small><?= e(fmt_datetime($h['changed_at'])) ?><?= $h['username'] ? ' &middot; ' . e($h['username']) : '' ?></small></li><?php endforeach; ?>
      </ol></div>
  </section>
</div>
<?php
    include ROOT_PATH . '/includes/admin-footer.php';
    exit;
}

// ---------- LIST VIEW ----------
$q = mb_substr(get_str('q'), 0, 60);
$status = get_str('status'); if (!in_array($status, booking_statuses(), true)) { $status = ''; }
$dest = get_int('dest');
$from = get_str('from'); if ($from !== '' && !valid_date($from)) { $from = ''; }
$to = get_str('to');     if ($to !== '' && !valid_date($to)) { $to = ''; }
$sorts = ['newest' => ['Newest requests', 'b.created_at DESC, b.booking_id DESC'], 'travel' => ['Travel date', 'b.travel_date ASC'], 'status' => ['Status', 'b.status ASC, b.created_at DESC']];
$sort = get_str('sort', 'newest'); if (!isset($sorts[$sort])) { $sort = 'newest'; }
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(b.reference LIKE ? OR u.username LIKE ? OR i.name LIKE ?)'; array_push($args, like($q), like($q), like($q)); }
if ($status !== '') { $where[] = 'b.status = ?'; $args[] = $status; }
if ($dest) { $where[] = 'b.item_id = ?'; $args[] = $dest; }
if ($from !== '') { $where[] = 'b.travel_date >= ?'; $args[] = $from; }
if ($to !== '') { $where[] = 'b.travel_date <= ?'; $args[] = $to; }
$whereSql = implode(' AND ', $where);
$c = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN items i ON i.item_id = b.item_id WHERE $whereSql"); $c->execute($args);
$total = (int) $c->fetchColumn();
$perPage = (int) cfg('admin_per_page', 10);
[$page, $pages, $offset] = paginate($total, $perPage);
$st = $pdo->prepare("SELECT b.booking_id, b.reference, b.travel_date, b.travelers, b.status, b.created_at, u.username, i.name AS destination
                       FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN items i ON i.item_id = b.item_id
                      WHERE $whereSql ORDER BY {$sorts[$sort][1]} LIMIT $perPage OFFSET $offset");
$st->execute($args); $rows = $st->fetchAll();
$destOptions = $pdo->query('SELECT item_id, name FROM items ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
$params = keep_params(['q', 'status', 'dest', 'from', 'to', 'sort']);
$filtered = $q !== '' || $status !== '' || $dest || $from !== '' || $to !== '';

$pageTitle = 'Booking management'; $navActive = 'bookings';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title"><div><p class="eyebrow">Bookings</p><h1>Booking management</h1><p>Review requests and update their status.</p></div></div>
<form class="admin-filter" method="get" role="search">
  <div class="field"><label for="q">Search reference, user, destination</label><input id="q" type="search" name="q" value="<?= e($q) ?>" maxlength="60"></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All</option><?php foreach (booking_statuses() as $s): ?><option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="dest">Destination</label><select id="dest" name="dest"><option value="">All</option><?php foreach ($destOptions as $k => $n): ?><option value="<?= (int) $k ?>"<?= $dest === (int) $k ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="from">Travel from</label><input id="from" type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field"><label for="to">Travel to</label><input id="to" type="date" name="to" value="<?= e($to) ?>"></div>
  <div class="field"><label for="sort">Sort</label><select id="sort" name="sort"><?php foreach ($sorts as $k => [$l]): ?><option value="<?= e($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--amber btn--sm" type="submit">Apply</button>
</form>
<?php if (!$rows): ?>
<div class="empty"><h2><?= $filtered ? 'No matching bookings' : 'No bookings yet' ?></h2><p><?= $filtered ? 'Try different filters.' : 'Booking requests will appear here.' ?></p><?php if ($filtered): ?><a class="btn btn--ghost" href="<?= e(url('admin/bookings.php')) ?>">Clear filters</a><?php endif; ?></div>
<?php else: ?>
<p class="muted"><?= $total ?> booking<?= $total === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Reference</th><th>Traveller</th><th>Destination</th><th>Travel date</th><th class="num">Travelers</th><th>Status</th><th>Requested</th><th>Action</th></tr></thead>
  <tbody>
<?php foreach ($rows as $r): ?>
    <tr><td class="mono"><?= e($r['reference']) ?></td><td><?= e($r['username']) ?></td><td><?= e($r['destination']) ?></td><td><?= e(fmt_date($r['travel_date'])) ?></td><td class="num"><?= (int) $r['travelers'] ?></td><td><?= badge($r['status']) ?></td><td><?= e(fmt_date($r['created_at'])) ?></td>
      <td><a class="btn btn--ghost btn--sm" href="<?= e(url('admin/bookings.php?id=' . (int) $r['booking_id'])) ?>">View</a></td></tr>
<?php endforeach; ?>
  </tbody>
</table></div>
<?= pagination_html($page, $pages, $params) ?>
<?php endif; ?>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>
