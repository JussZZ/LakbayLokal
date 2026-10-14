<?php
require __DIR__ . '/includes/bootstrap.php';

$pdo = db();
// Published destinations drive the hero, featured grid and inspiration blocks.
$dests = $pdo->query(
    "SELECT item_id, name, location, category, description, image_path, best_season, highlights, price_per_person
       FROM items WHERE status = 'published' ORDER BY item_id ASC LIMIT 8"
)->fetchAll();
$catCounts = $pdo->query("SELECT category, COUNT(*) AS c FROM items WHERE status = 'published' GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

$slides = [];
foreach ($dests as $d) {
    $id = (int) $d['item_id'];
    $slides[] = [
        'id'          => $id,
        'name'        => $d['name'],
        'location'    => $d['location'],
        'description' => mb_strimwidth($d['description'], 0, 175, '…'),
        'image'       => image_url($d['image_path']),
        'hue'         => hue_for($id),
        'url'         => url('destination-details.php?id=' . $id),
        'bookUrl'     => url('user/create-booking.php?item=' . $id),
    ];
}
$first = $slides[0] ?? null;
$u = current_user();
$inspiration = array_slice(array_reverse(array_values(array_filter($dests, fn($d) => !empty($d['best_season'])))), 0, 3);

$pageTitle  = 'Discover local destinations';
$navActive  = 'home';
$navOverlay = true;
$bodyClass  = 'page-home';
$extraJs    = $slides ? ['destination-slider.js'] : [];
include ROOT_PATH . '/includes/header.php';
?>
<section class="hero<?= $first ? '' : ' hero--empty' ?>" data-hero aria-roledescription="carousel" aria-label="Featured destinations">
  <div class="hero__bg" aria-hidden="true">
    <div class="hero__layer is-active is-placeholder" style="--hue:<?= $first ? (int) $first['hue'] : 210 ?>"></div>
    <div class="hero__layer is-placeholder" style="--hue:<?= $first ? (int) $first['hue'] : 210 ?>"></div>
  </div>
  <div class="hero__shade" aria-hidden="true"></div>

  <div class="wrap hero__inner" id="main">
    <div>
      <h1 class="hero__tagline">LakbayLokal &mdash; Wander closer to home</h1>
<?php if ($first): ?>
      <div class="hero__copy" data-hero-copy aria-live="polite">
        <p class="hero__loc" data-hero-location><?= e($first['location']) ?></p>
        <h2 class="hero__title" data-hero-title><?= e($first['name']) ?></h2>
        <p class="hero__desc" data-hero-desc><?= e($first['description']) ?></p>
        <div class="hero__actions">
          <a class="btn btn--amber btn--square" data-hero-book href="<?= e($first['bookUrl']) ?>" aria-label="Book <?= e($first['name']) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/></svg>
          </a>
          <a class="btn btn--ghost" data-hero-link href="<?= e($first['url']) ?>">Discover location</a>
        </div>
      </div>
<?php else: ?>
      <div class="hero__copy">
        <h2 class="hero__title">No destinations yet</h2>
        <p class="hero__desc">Published destinations will appear here, with their photos, as soon as an administrator adds them.</p>
        <div class="hero__actions">
<?php if ($u && $u['role'] === 'admin'): ?>
          <a class="btn btn--amber" href="<?= e(url('admin/destinations.php?action=new')) ?>">Add a destination</a>
<?php else: ?>
          <a class="btn btn--amber" href="<?= e(url('destinations.php')) ?>">Browse destinations</a>
<?php endif; ?>
        </div>
      </div>
<?php endif; ?>
    </div>

<?php if ($slides): ?>
    <div class="hero__rail" role="group" aria-label="Choose a destination">
<?php foreach ($slides as $i => $s): ?>
      <button class="thumb<?= $i === 0 ? ' is-active' : '' ?>" type="button" data-index="<?= $i ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Show <?= e($s['name']) ?>">
        <?= image_or_placeholder($dests[$i]['image_path'], $s['name'], (int) $s['hue'], 'cover') ?>
        <span class="thumb__label"><small><?= e($s['location']) ?></small><b><?= e($s['name']) ?></b></span>
      </button>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>

<?php if ($slides): ?>
  <div class="hero__controls">
    <div class="wrap">
      <button class="circle-btn" type="button" data-hero-prev aria-label="Previous destination">&#8249;</button>
      <button class="circle-btn" type="button" data-hero-next aria-label="Next destination">&#8250;</button>
      <div class="hero__progress" aria-hidden="true"><i data-hero-bar style="--p:<?= 1 / count($slides) ?>"></i></div>
      <div class="hero__count" aria-label="Destination number"><span data-hero-current>01</span><small>/ <?= str_pad((string) count($slides), 2, '0', STR_PAD_LEFT) ?></small></div>
    </div>
  </div>
  <script type="application/json" id="heroData"><?= json_encode($slides, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
</section>

<!-- Featured destinations -->
<section class="section" aria-labelledby="featuredH">
  <div class="wrap">
    <div class="section-head">
      <div><p class="eyebrow">Featured</p><h2 class="h2" id="featuredH">Places worth the trip</h2></div>
      <a class="btn btn--ghost arrow" href="<?= e(url('destinations.php')) ?>">All destinations</a>
    </div>
<?php if (!$dests): ?>
    <div class="empty"><h3>Nothing published yet</h3><p>Featured destinations will show up here once they are added.</p></div>
<?php else: ?>
    <div class="feature-grid">
<?php foreach (array_slice($dests, 0, 4) as $d): ?>
      <a class="feature" href="<?= e(url('destination-details.php?id=' . (int) $d['item_id'])) ?>">
        <?= image_or_placeholder($d['image_path'], $d['name'], hue_for((int) $d['item_id']), 'cover') ?>
        <div class="feature__body">
          <span class="feature__loc"><?= e($d['location']) ?></span>
          <h3 class="feature__name"><?= e($d['name']) ?></h3>
          <p class="feature__desc"><?= e($d['description']) ?></p>
          <span class="feature__more">Explore details &rarr;</span>
        </div>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
</section>

<!-- Why LakbayLokal -->
<section class="section" aria-labelledby="whyH">
  <div class="wrap">
    <div class="section-head"><div><p class="eyebrow">Why LakbayLokal</p><h2 class="h2" id="whyH">Simple, local, accountable</h2></div></div>
    <div class="why-grid">
      <div class="why"><span class="why__n">01</span><h3>Local first</h3><p>Every listing is a place within reach, described plainly with what to expect and when to go.</p></div>
      <div class="why"><span class="why__n">02</span><h3>Request, then follow it</h3><p>Send a booking request with your date and group size, then track its status from your own account.</p></div>
      <div class="why"><span class="why__n">03</span><h3>Reviewed by people</h3><p>Accounts are approved and every booking request is reviewed by an administrator before it is confirmed.</p></div>
    </div>
  </div>
</section>

<!-- Booking call to action -->
<section class="cta" aria-labelledby="ctaH">
  <div class="wrap">
    <p class="eyebrow">Plan your lakbay</p>
    <h2 class="display" id="ctaH">Pick a place. Send a request.</h2>
    <p class="lead">Browse the destinations, choose a date and let us confirm your trip.</p>
    <p>
      <a class="btn btn--amber arrow" href="<?= e(url('destinations.php')) ?>">Explore destinations</a>
<?php if (!$u): ?>
      <a class="btn btn--ghost" href="<?= e(url('user/register.php')) ?>">Create an account</a>
<?php elseif ($u['role'] === 'user'): ?>
      <a class="btn btn--ghost" href="<?= e(url('user/bookings.php')) ?>">My bookings</a>
<?php endif; ?>
    </p>
  </div>
</section>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
