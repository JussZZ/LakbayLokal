<?php
require __DIR__ . '/includes/bootstrap.php';
$pdo = db();

$q    = mb_substr(get_str('q'), 0, 80);
$cat  = get_str('category');
$sort = get_str('sort', 'newest');
if ($cat !== '' && !isset(categories()[$cat])) { $cat = ''; }
// Allow-list: user input only selects a key, never becomes SQL.
$sorts = [
    'newest'     => ['Newest first', 'item_id DESC'],
    'name'       => ['Name A-Z', 'name ASC'],
    'price_low'  => ['Price: low to high', 'price_per_person ASC'],
    'price_high' => ['Price: high to low', 'price_per_person DESC'],
];
if (!isset($sorts[$sort])) { $sort = 'newest'; }

$where = ["status = 'published'"]; $args = [];
if ($q !== '')   { $where[] = '(name LIKE :q1 OR location LIKE :q2)'; $args[':q1'] = $args[':q2'] = like($q); }
if ($cat !== '') { $where[] = 'category = :cat'; $args[':cat'] = $cat; }
$whereSql = implode(' AND ', $where);

$st = $pdo->prepare("SELECT COUNT(*) FROM items WHERE $whereSql");
$st->execute($args);
$total = (int) $st->fetchColumn();
[$page, $pages, $offset] = paginate($total, (int) cfg('per_page', 9));
$perPage = (int) cfg('per_page', 9);

$st = $pdo->prepare("SELECT item_id, name, location, category, description, image_path, price_per_person
                       FROM items WHERE $whereSql ORDER BY {$sorts[$sort][1]} LIMIT $perPage OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();

$u = current_user();
$pageTitle = 'Destinations';
$navActive = 'destinations';
include ROOT_PATH . '/includes/header.php';
$params = keep_params(['q', 'category', 'sort']);
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Destinations</p>
    <h1 class="display">Find your next place</h1>
    <p class="lead">Search by name or location, filter by the kind of experience you want, then open a destination for details and booking.</p>
  </div>
</section>

<main class="wrap page-pad" id="main">
  <form class="filter-bar" method="get" action="<?= e(url('destinations.php')) ?>" role="search">
    <div class="field"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= e($q) ?>" maxlength="80" placeholder="Name or location"></div>
    <div class="field"><label for="category">Experience</label>
      <select id="category" name="category">
        <option value="">All experiences</option>
<?php foreach (categories() as $k => $label): ?>
        <option value="<?= e($k) ?>"<?= $cat === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select></div>
    <div class="field"><label for="sort">Sort by</label>
      <select id="sort" name="sort">
<?php foreach ($sorts as $k => [$label]): ?>
        <option value="<?= e($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select></div>
    <div><button class="btn btn--amber" type="submit">Search</button></div>
  </form>

  <p class="muted" aria-live="polite"><?= $total ?> <?= $total === 1 ? 'destination' : 'destinations' ?> found<?= ($q !== '' || $cat !== '') ? ' &middot; <a href="' . e(url('destinations.php')) . '">Clear filters</a>' : '' ?></p>

<?php if (!$rows): ?>
  <div class="empty">
    <h2>No destinations match</h2>
    <p><?= ($q !== '' || $cat !== '') ? 'Try a different keyword or experience, or clear your filters.' : 'No destinations have been published yet. Please check back soon.' ?></p>
<?php if ($q !== '' || $cat !== ''): ?><a class="btn btn--ghost" href="<?= e(url('destinations.php')) ?>">Clear filters</a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="dest-grid">
<?php foreach ($rows as $d): ?>
    <a class="dcard" href="<?= e(url('destination-details.php?id=' . (int) $d['item_id'])) ?>">
      <?= image_or_placeholder($d['image_path'], $d['name'], hue_for((int) $d['item_id']), 'cover') ?>
      <span class="dcard__tag"><?= e(categories()[$d['category']] ?? $d['category']) ?></span>
      <div class="dcard__body">
        <span class="dcard__loc"><?= e($d['location']) ?></span>
        <h2 class="dcard__name"><?= e($d['name']) ?></h2>
        <p class="dcard__desc"><?= e($d['description']) ?></p>
        <div class="dcard__foot"><span class="dcard__price"><?= $d['price_per_person'] > 0 ? 'From ' . e(money($d['price_per_person'])) : 'Ask for rate' ?></span><span>View details &rarr;</span></div>
      </div>
    </a>
<?php endforeach; ?>
  </div>
  <?= pagination_html($page, $pages, $params) ?>
<?php endif; ?>
</main>

