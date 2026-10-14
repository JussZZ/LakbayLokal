<?php
require __DIR__ . '/includes/bootstrap.php';
$pdo = db();
$id = get_int('id');

$st = $pdo->prepare("SELECT * FROM items WHERE item_id = :id AND status = 'published'");
$st->execute([':id' => $id]);
$d = $st->fetch();
if (!$d) {
    render_error_page(404, 'Destination not found', 'This destination does not exist or is not published.');
}

// Related destinations: same category, excluding this one.
$st = $pdo->prepare("SELECT item_id, name, location, category, description, image_path, price_per_person
                       FROM items WHERE status = 'published' AND category = :c AND item_id <> :id ORDER BY item_id DESC LIMIT 3");
$st->execute([':c' => $d['category'], ':id' => $id]);
$related = $st->fetchAll();

$highlights = array_values(array_filter(array_map('trim', explode("\n", (string) $d['highlights']))));
$u = current_user();
$bookUrl = url('user/create-booking.php?item=' . $id);
$hue = hue_for($id);

$pageTitle = $d['name'];
$navActive = 'destinations';
$navOverlay = true;
include ROOT_PATH . '/includes/header.php';
?>
<section class="detail-hero">
  <div class="detail-hero__media"><?= image_or_placeholder($d['image_path'], $d['name'], $hue, 'cover') ?></div>
  <div class="wrap">
    <p class="crumbs"><a href="<?= e(url('index.php')) ?>">Home</a> / <a href="<?= e(url('destinations.php')) ?>">Destinations</a> / <?= e($d['name']) ?></p>
    <p class="eyebrow"><?= e(categories()[$d['category']] ?? $d['category']) ?></p>
    <h1 class="display"><?= e($d['name']) ?></h1>
    <p class="lead"><?= e($d['location']) ?></p>
  </div>
</section>

<main class="wrap detail-grid" id="main">
  <article>
    <h2 class="h2">About this place</h2>
    <div class="prose"><p><?= nl2br(e($d['description'])) ?></p></div>

    <h2 class="h2" style="margin-top:2.6rem">Highlights</h2>
<?php if ($highlights): ?>
    <ul class="highlights">
<?php foreach ($highlights as $h): ?><li><?= e($h) ?></li><?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="muted">Highlights for this destination have not been added yet.</p>
<?php endif; ?>

    <div class="facts">
      <div class="fact"><span>Category</span><?= e(categories()[$d['category']] ?? $d['category']) ?></div>
      <div class="fact"><span>Best time to go</span><?= $d['best_season'] ? e($d['best_season']) : 'Not specified' ?></div>
      <div class="fact"><span>Location</span><?= e($d['location']) ?></div>
      <div class="fact"><span>Slots remaining</span><?= (int) $d['quantity'] ?></div>
    </div>
  </article>

  <aside class="book-panel" aria-label="Booking">
    <p class="eyebrow">Book this trip</p>
<?php if ($d['price_per_person'] > 0): ?>
    <div class="book-panel__price"><?= e(money($d['price_per_person'])) ?><small>per person</small></div>
<?php else: ?>
    <div class="book-panel__price">Ask for rate</div>
<?php endif; ?>
    <p class="muted" style="margin-top:.8rem">Send a booking request. It stays pending until an administrator confirms it. No online payment is taken.</p>
<?php if ((int) $d['quantity'] < 1): ?>
    <span class="btn btn--amber btn--block is-disabled" aria-disabled="true">Fully booked</span>
<?php elseif (!$u): ?>
    <a class="btn btn--amber btn--block" href="<?= e(url('user/login.php?next=' . urlencode('user/create-booking.php?item=' . $id))) ?>">Log in to book</a>
    <p class="hint">New here? <a href="<?= e(url('user/register.php')) ?>">Create an account</a></p>
<?php elseif ($u['role'] === 'admin'): ?>
    <p class="hint">Administrators cannot place bookings. Use a traveller account.</p>
<?php else: ?>
    <a class="btn btn--amber btn--block" href="<?= e($bookUrl) ?>">Request a booking</a>
<?php endif; ?>
  </aside>
</main>

<section class="section section--alt">
  <div class="wrap">
    <div class="section-head"><h2 class="h2">More like this</h2><a class="btn btn--ghost arrow" href="<?= e(url('destinations.php?category=' . urlencode($d['category']))) ?>">See the category</a></div>
<?php if ($related): ?>
    <div class="related">
<?php foreach ($related as $r): ?>
      <a class="dcard" href="<?= e(url('destination-details.php?id=' . (int) $r['item_id'])) ?>">
        <?= image_or_placeholder($r['image_path'], $r['name'], hue_for((int) $r['item_id']), 'cover') ?>
        <div class="dcard__body"><span class="dcard__loc"><?= e($r['location']) ?></span><h3 class="dcard__name"><?= e($r['name']) ?></h3></div>
      </a>
<?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty"><h3>No related destinations yet</h3><p>Other places in this category will show up here when they are published.</p></div>
<?php endif; ?>
  </div>
</section>

