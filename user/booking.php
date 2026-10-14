<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_user();
$pdo = db();
$id = is_post() ? post_int('id') : get_int('id');

// Ownership check lives in the query itself: another user's id simply returns "not found" (no IDOR).
$st = $pdo->prepare("SELECT b.*, i.name AS destination, i.location, i.image_path, i.price_per_person, i.item_id AS dest_id
                       FROM bookings b JOIN items i ON i.item_id = b.item_id
                      WHERE b.booking_id = :id AND b.user_id = :u");
$st->execute([':id' => $id, ':u' => $u['user_id']]);
$b = $st->fetch();
if (!$b) {
  render_error_page(404, 'Booking not found', 'We could not find that booking in your account.');
}

if (is_post() && post_str('action') === 'cancel') {
  csrf_verify();
  $pdo->beginTransaction();
  try {
    $up = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :id AND user_id = :u AND status = 'pending'");
    $up->execute([':id' => $id, ':u' => $u['user_id']]);
    if ($up->rowCount() === 1) {
      $h = $pdo->prepare("INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, note) VALUES (:b, 'pending', 'cancelled', :u, 'Cancelled by traveller')");
      $h->execute([':b' => $id, ':u' => $u['user_id']]);
      log_activity((int) $u['user_id'], 'booking.cancel', $b['reference'] . ' cancelled by traveller');
      $pdo->commit();
      flash('success', 'Your booking request has been cancelled.');
    } else {
      $pdo->rollBack();
      flash('error', 'Only pending bookings can be cancelled here. Please contact LakbayLokal for changes.');
    }
  } catch (Throwable $ex) {
    $pdo->rollBack();
    throw $ex;
  }
  redirect('user/booking.php?id=' . $id);
}

$st = $pdo->prepare('SELECT new_status, note, changed_at FROM booking_status_history WHERE booking_id = :b ORDER BY history_id');
$st->execute([':b' => $id]);
$history = $st->fetchAll();

$pageTitle = 'Booking ' . $b['reference'];
$navActive = 'bookings';
$extraCss = ['dashboard.css'];
include ROOT_PATH . '/includes/header.php';
?>
<main class="wrap page-pad" id="main">
  <p class="crumbs"><a href="<?= e(url('user/bookings.php')) ?>">My bookings</a> / <?= e($b['reference']) ?></p>
  <div class="dash-head">
    <div>
      <p class="eyebrow">Booking reference</p>
      <h1 class="display mono" style="font-family:var(--display)"><?= e($b['reference']) ?></h1>
    </div>
    <?= badge($b['status']) ?>
  </div>
  <div class="booking-layout">
    <div>
      <div class="panel">
        <h2>Trip details</h2>
        <div class="detail-list" style="margin-top:1rem">
          <div><span>Destination</span><a href="<?= e(url('destination-details.php?id=' . (int) $b['dest_id'])) ?>"><?= e($b['destination']) ?></a></div>
          <div><span>Location</span><?= e($b['location']) ?></div>
          <div><span>Travel date</span><?= e(fmt_date($b['travel_date'], 'l, M j, Y')) ?></div>
          <div><span>Travelers</span><?= (int) $b['travelers'] ?></div>
          <div><span>Contact name</span><?= e($b['contact_name']) ?></div>
          <div><span>Contact email</span><?= e($b['contact_email']) ?></div>
          <div><span>Contact phone</span><?= $b['contact_phone'] ? e($b['contact_phone']) : '—' ?></div>
          <div><span>Estimated total</span><?= $b['price_per_person'] > 0 ? e(money($b['price_per_person'] * $b['travelers'])) : 'Ask for rate' ?></div>
        </div>
        <?php if ($b['special_requests']): ?>
          <p style="margin-top:1.4rem"><span class="muted">Special requests</span><br><?= nl2br(e($b['special_requests'])) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($b['status'] === 'pending'): ?>
        <form method="post" data-confirm="Cancel this booking request? This cannot be undone." data-confirm-label="Cancel booking" style="margin-top:1.4rem">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['booking_id'] ?>"><input type="hidden" name="action" value="cancel">
          <button class="btn btn--danger" type="submit">Cancel request</button>
        </form>
      <?php endif; ?>
    </div>
    <aside class="panel">
      <h2>Status timeline</h2>
      <ol class="timeline">
        <?php foreach ($history as $h): ?>
          <li><strong><?= e(ucfirst($h['new_status'])) ?></strong><?= $h['note'] ? ' &mdash; ' . e($h['note']) : '' ?><small><?= e(fmt_datetime($h['changed_at'])) ?></small></li>
        <?php endforeach; ?>
      </ol>
      <p class="hint">Amounts are estimates. No online payment is processed.</p>
    </aside>
  </div>
</main>