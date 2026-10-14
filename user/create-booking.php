<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_approved_user();     // pending / suspended accounts are stopped here
$pdo = db();

$itemId = is_post() ? post_int('item_id') : get_int('item');
$st = $pdo->prepare("SELECT item_id, name, location, image_path, price_per_person, quantity FROM items WHERE item_id = :id AND status = 'published'");
$st->execute([':id' => $itemId]);
$item = $st->fetch();

if (!$item) {
  // No (valid) destination chosen yet: let the traveller pick one.
  $pageTitle = 'Book a destination';
  $navActive = 'destinations';
  $extraCss = ['dashboard.css'];
  $list = $pdo->query("SELECT item_id, name, location FROM items WHERE status = 'published' AND quantity > 0 ORDER BY name")->fetchAll();
  include ROOT_PATH . '/includes/header.php';
  echo '<main class="wrap page-pad" id="main"><p class="eyebrow">New booking</p><h1 class="display">Choose a destination</h1>';
  if (!$list) {
    echo '<div class="empty"><h2>Nothing available right now</h2><p>No destinations are open for booking at the moment.</p></div>';
  } else {
    echo '<div class="quick" style="max-width:40rem;margin-top:2rem">';
    foreach ($list as $r) {
      echo '<a href="' . e(url('user/create-booking.php?item=' . (int) $r['item_id'])) . '"><span>' . e($r['name']) . ' <span class="muted">&middot; ' . e($r['location']) . '</span></span></a>';
    }
    echo '</div>';
  }
  echo '</main>';
  include ROOT_PATH . '/includes/footer.php';
  exit;
}

$maxTrav = max(1, min((int) cfg('max_travelers', 20), (int) $item['quantity']));
$minDate = date('Y-m-d', strtotime('+1 day'));
$maxDate = date('Y-m-d', strtotime('+2 years'));
$errors = [];
$v = ['travel_date' => '', 'travelers' => '1', 'contact_name' => $u['full_name'], 'contact_email' => $u['email'], 'contact_phone' => (string) $u['phone'], 'special_requests' => ''];

if (is_post()) {
  csrf_verify();
  foreach ($v as $k => $_) {
    $v[$k] = post_str($k);
  }
  if ((int) $item['quantity'] < 1) {
    $errors['travelers'] = 'This destination is fully booked.';
  }
  if (!valid_date($v['travel_date'])) {
    $errors['travel_date'] = 'Choose a valid travel date.';
  } elseif ($v['travel_date'] < $minDate) {
    $errors['travel_date'] = 'Travel date must be tomorrow or later.';
  } elseif ($v['travel_date'] > $maxDate) {
    $errors['travel_date'] = 'Travel date must be within the next two years.';
  }
  if (!ctype_digit($v['travelers']) || (int) $v['travelers'] < 1) {
    $errors['travelers'] = 'Enter a whole number of travelers (at least 1).';
  } elseif ((int) $v['travelers'] > $maxTrav) {
    $errors['travelers'] = 'This booking allows up to ' . $maxTrav . ' travelers.';
  }
  if ($v['contact_name'] === '' || mb_strlen($v['contact_name']) < 2 || mb_strlen($v['contact_name']) > 120) {
    $errors['contact_name'] = 'Enter the contact name (2-120 characters).';
  }
  if (!valid_email($v['contact_email'])) {
    $errors['contact_email'] = 'Enter a valid email address.';
  }
  if ($v['contact_phone'] !== '' && !valid_phone($v['contact_phone'])) {
    $errors['contact_phone'] = 'Enter a valid phone number.';
  }
  if (mb_strlen($v['special_requests']) > 500) {
    $errors['special_requests'] = 'Special requests must be 500 characters or fewer.';
  }

  if (!$errors) {
    $pdo->beginTransaction();
    try {
      $ref = generate_reference();
      $ins = $pdo->prepare("INSERT INTO bookings (reference, user_id, item_id, travel_date, travelers, contact_name, contact_email, contact_phone, special_requests, status)
                                  VALUES (:r, :u, :i, :d, :t, :n, :e, :p, :s, 'pending')");
      $ins->execute([
        ':r' => $ref,
        ':u' => $u['user_id'],
        ':i' => $item['item_id'],
        ':d' => $v['travel_date'],
        ':t' => (int) $v['travelers'],
        ':n' => $v['contact_name'],
        ':e' => $v['contact_email'],
        ':p' => $v['contact_phone'] !== '' ? $v['contact_phone'] : null,
        ':s' => $v['special_requests'] !== '' ? $v['special_requests'] : null
      ]);
      $bid = (int) $pdo->lastInsertId();
      $h = $pdo->prepare("INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, note) VALUES (:b, NULL, 'pending', :u, 'Booking requested')");
      $h->execute([':b' => $bid, ':u' => $u['user_id']]);
      log_activity((int) $u['user_id'], 'booking.create', $ref . ' for ' . $item['name']);
      $pdo->commit();
    } catch (Throwable $ex) {
      $pdo->rollBack();
      throw $ex;
    }
    flash('success', 'Booking request sent! Your reference is ' . $ref . '. It is pending until an administrator confirms it.');
    redirect('user/booking.php?id=' . $bid);
  }
}

$pageTitle = 'Book ' . $item['name'];
$navActive = 'destinations';
$extraCss = ['dashboard.css'];
include ROOT_PATH . '/includes/header.php';
?>
<main class="wrap page-pad" id="main">
  <p class="eyebrow">New booking</p>
  <h1 class="display">Request a booking</h1>
  <div class="booking-layout" style="margin-top:2rem">
    <div class="panel">
      <?= error_summary($errors) ?>
      <form method="post" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
        <div class="field">
          <label for="dest_name">Destination</label>
          <input id="dest_name" type="text" value="<?= e($item['name']) ?>" readonly aria-describedby="dest_hint">
          <p class="hint" id="dest_hint"><a href="<?= e(url('user/create-booking.php')) ?>">Choose a different destination</a></p>
        </div>
        <div class="form-row">
          <?= form_field(['name' => 'travel_date', 'label' => 'Travel date', 'type' => 'date', 'value' => $v['travel_date'], 'required' => true, 'errors' => $errors, 'attrs' => ['min' => $minDate, 'max' => $maxDate]]) ?>
          <?= form_field(['name' => 'travelers', 'label' => 'Number of travelers', 'type' => 'number', 'value' => $v['travelers'], 'required' => true, 'errors' => $errors, 'hint' => 'Up to ' . $maxTrav . '.', 'attrs' => ['min' => 1, 'max' => $maxTrav, 'step' => 1, 'inputmode' => 'numeric']]) ?>
        </div>
        <?= form_field(['name' => 'contact_name', 'label' => 'Contact name', 'value' => $v['contact_name'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 120]]) ?>
        <div class="form-row">
          <?= form_field(['name' => 'contact_email', 'label' => 'Contact email', 'type' => 'email', 'value' => $v['contact_email'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 150]]) ?>
          <?= form_field(['name' => 'contact_phone', 'label' => 'Contact phone', 'type' => 'tel', 'value' => $v['contact_phone'], 'errors' => $errors, 'attrs' => ['maxlength' => 20, 'pattern' => '[0-9+\(\)\-\s]{7,20}', 'data-pattern-message' => 'Use digits, spaces, + ( ) or - (7-20 characters).']]) ?>
        </div>
        <?= form_field(['name' => 'special_requests', 'label' => 'Special requests (optional)', 'type' => 'textarea', 'value' => $v['special_requests'], 'errors' => $errors, 'attrs' => ['maxlength' => 500, 'rows' => 4]]) ?>
        <button class="btn btn--amber" type="submit">Send booking request</button>
      </form>
    </div>
    <aside class="summary" aria-label="Booking summary">
      <div class="summary__img"><?= image_or_placeholder($item['image_path'], $item['name'], hue_for((int) $item['item_id']), 'cover') ?></div>
      <div class="summary__body">
        <h2><?= e($item['name']) ?></h2>
        <p class="muted"><?= e($item['location']) ?></p>
        <dl>
          <dt>Price per person</dt>
          <dd><?= $item['price_per_person'] > 0 ? e(money($item['price_per_person'])) : 'Ask for rate' ?></dd>
          <dt>Slots remaining</dt>
          <dd><?= (int) $item['quantity'] ?></dd>
          <dt>Estimated total</dt>
          <dd class="total" id="estTotal" data-price="<?= e($item['price_per_person']) ?>" data-symbol="<?= e(cfg('currency_symbol')) ?>">—</dd>
        </dl>
        <p class="hint">Estimate only. Your request is pending until confirmed, and no online payment is taken.</p>
      </div>
    </aside>
  </div>
</main>