<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pdo = db();
const UPLOAD_DIR_REL = 'assets/images/destinations/uploads/';

/** Secure optional image upload: size, MIME (finfo), extension, real image check, random name. */
function handle_image_upload(array $f, ?string &$err): ?string
{
    if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) { return null; }
    if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'The image could not be uploaded (error ' . (int) $f['error'] . ').'; return null; }
    if ($f['size'] > (int) cfg('upload_max_bytes')) { $err = 'Image must be ' . round(cfg('upload_max_bytes') / 1048576) . ' MB or smaller.'; return null; }
    if (!is_uploaded_file($f['tmp_name'])) { $err = 'Invalid upload.'; return null; }
    $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || !isset($types[$mime]) || @getimagesize($f['tmp_name']) === false) {
        $err = 'Only JPG, PNG or WebP images are allowed.'; return null;
    }
    $dir = ROOT_PATH . '/' . UPLOAD_DIR_REL;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) { $err = 'Upload folder is not writable.'; return null; }
    $name = bin2hex(random_bytes(12)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) { $err = 'Could not save the image.'; return null; }
    return UPLOAD_DIR_REL . $name;
}
function delete_uploaded_image(?string $path): void
{
    if ($path && strpos($path, UPLOAD_DIR_REL) === 0 && preg_match(IMAGE_PATH_RE, $path) && is_file(ROOT_PATH . '/' . $path)) { @unlink(ROOT_PATH . '/' . $path); }
}

$action = get_str('action');
$errors = []; $editId = 0;
$blank = ['name' => '', 'location' => '', 'category' => 'nature', 'description' => '', 'highlights' => '', 'best_season' => '', 'price_per_person' => '0.00', 'quantity' => '10', 'status' => 'draft', 'image_path' => ''];
$v = $blank;

if (is_post()) {
    csrf_verify();
    $do = post_str('do');
    if ($do === 'delete') {                                   // ---------- DELETE ----------
        $id = post_int('item_id');
        $st = $pdo->prepare('SELECT item_id, name, image_path FROM items WHERE item_id = :id'); $st->execute([':id' => $id]); $it = $st->fetch();
        if (!$it) { flash('error', 'Destination not found.'); }
        else {
            $n = $pdo->prepare('SELECT (SELECT COUNT(*) FROM bookings WHERE item_id = :a) + (SELECT COUNT(*) FROM transactions WHERE item_id = :b)');
            $n->execute([':a' => $id, ':b' => $id]);
            if ((int) $n->fetchColumn() > 0) {
                flash('error', '"' . $it['name'] . '" has bookings or transactions, so it cannot be deleted. Set it to Draft to hide it instead.');
            } else {
                try {
                    $pdo->prepare('DELETE FROM items WHERE item_id = :id')->execute([':id' => $id]);
                    delete_uploaded_image($it['image_path']);
                    log_activity((int) $admin['user_id'], 'destination.delete', $it['name']);
                    flash('success', 'Destination "' . $it['name'] . '" was deleted.');
                } catch (PDOException $ex) {
                    if ($ex->getCode() === '23000') { flash('error', 'This destination is still referenced by other records.'); } else { throw $ex; }
                }
            }
        }
        redirect('admin/destinations.php');
    }
    if ($do === 'save') {                                     // ---------- CREATE / UPDATE ----------
        $editId = post_int('item_id');
        foreach ($blank as $k => $_) { $v[$k] = post_str($k, $blank[$k]); }
        if (mb_strlen($v['name']) < 2 || mb_strlen($v['name']) > 120) { $errors['name'] = 'Name is required (2-120 characters).'; }
        if (mb_strlen($v['location']) < 2 || mb_strlen($v['location']) > 150) { $errors['location'] = 'Location is required (2-150 characters).'; }
        if (!isset(categories()[$v['category']])) { $errors['category'] = 'Choose a category.'; }
        if (mb_strlen($v['description']) < 10 || mb_strlen($v['description']) > 2000) { $errors['description'] = 'Description is required (10-2000 characters).'; }
        if (mb_strlen($v['highlights']) > 1000) { $errors['highlights'] = 'Highlights must be 1000 characters or fewer.'; }
        if (mb_strlen($v['best_season']) > 100) { $errors['best_season'] = 'Best season must be 100 characters or fewer.'; }
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $v['price_per_person'])) { $errors['price_per_person'] = 'Enter a valid price, e.g. 2500 or 2500.50.'; }
        if (!ctype_digit($v['quantity']) || (int) $v['quantity'] > 10000) { $errors['quantity'] = 'Slots must be a whole number from 0 to 10000.'; }
        if (!in_array($v['status'], ['published', 'draft'], true)) { $errors['status'] = 'Choose a status.'; }
        if ($v['image_path'] !== '' && !preg_match(IMAGE_PATH_RE, $v['image_path'])) { $errors['image_path'] = 'Use a path like assets/images/destinations/destination-01.jpg (jpg, png or webp).'; }
        $old = null;
        if ($editId) {
            $st = $pdo->prepare('SELECT image_path FROM items WHERE item_id = :id'); $st->execute([':id' => $editId]);
            $old = $st->fetch();
            if (!$old) { flash('error', 'Destination not found.'); redirect('admin/destinations.php'); }
        }
        $upErr = null; $uploaded = null;
        if (!$errors) {
            $uploaded = handle_image_upload($_FILES['image_file'] ?? [], $upErr);
            if ($upErr) { $errors['image_file'] = $upErr; }
        }
        if (!$errors) {
            $img = $uploaded ?: ($v['image_path'] !== '' ? $v['image_path'] : null);
            $data = [':n' => $v['name'], ':l' => $v['location'], ':c' => $v['category'], ':d' => $v['description'], ':h' => $v['highlights'] !== '' ? $v['highlights'] : null,
                     ':s' => $v['best_season'] !== '' ? $v['best_season'] : null, ':p' => $v['price_per_person'], ':q' => (int) $v['quantity'], ':st' => $v['status'], ':i' => $img];
            if ($editId) {
                $data[':id'] = $editId;
                $pdo->prepare('UPDATE items SET name=:n, location=:l, category=:c, description=:d, highlights=:h, best_season=:s, price_per_person=:p, quantity=:q, status=:st, image_path=:i WHERE item_id=:id')->execute($data);
                if ($uploaded && $old['image_path'] !== $img) { delete_uploaded_image($old['image_path']); }
                log_activity((int) $admin['user_id'], 'destination.update', $v['name']);
                flash('success', 'Destination "' . $v['name'] . '" was updated.');
            } else {
                $pdo->prepare('INSERT INTO items (name, location, category, description, highlights, best_season, price_per_person, quantity, status, image_path) VALUES (:n,:l,:c,:d,:h,:s,:p,:q,:st,:i)')->execute($data);
                log_activity((int) $admin['user_id'], 'destination.create', $v['name']);
                flash('success', 'Destination "' . $v['name'] . '" was created.');
            }
            redirect('admin/destinations.php');
        }
        $action = $editId ? 'edit' : 'new';
    }
}

// ---------- FORM VIEW ----------
if ($action === 'new' || $action === 'edit') {
    if ($action === 'edit' && !is_post()) {
        $editId = get_int('id');
        $st = $pdo->prepare('SELECT * FROM items WHERE item_id = :id'); $st->execute([':id' => $editId]); $row = $st->fetch();
        if (!$row) { flash('error', 'Destination not found.'); redirect('admin/destinations.php'); }
        foreach ($blank as $k => $_) { $v[$k] = (string) ($row[$k] ?? ''); }
    }
    $pageTitle = $editId ? 'Edit destination' : 'New destination'; $navActive = 'destinations';
    include ROOT_PATH . '/includes/admin-sidebar.php';
    $preview = image_or_placeholder($v['image_path'], $v['name'] ?: 'Destination', hue_for($editId ?: 1), 'cover');
?>
<div class="admin-title"><div><p class="eyebrow">Destinations</p><h1><?= $editId ? 'Edit destination' : 'New destination' ?></h1></div>
  <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/destinations.php')) ?>">&larr; Back to list</a></div>
<form method="post" enctype="multipart/form-data" class="admin-form" data-validate>
  <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="item_id" value="<?= (int) $editId ?>">
  <?= error_summary($errors) ?>
  <div class="panel">
    <h2>Details</h2>
    <div class="form-row">
      <?= form_field(['name' => 'name', 'label' => 'Name', 'value' => $v['name'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 120]]) ?>
      <?= form_field(['name' => 'location', 'label' => 'Location', 'value' => $v['location'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 150]]) ?>
    </div>
    <?= form_field(['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'value' => $v['description'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 2000, 'rows' => 5, 'minlength' => 10]]) ?>
    <?= form_field(['name' => 'highlights', 'label' => 'Highlights / activities', 'type' => 'textarea', 'value' => $v['highlights'], 'errors' => $errors, 'hint' => 'One per line.', 'attrs' => ['maxlength' => 1000, 'rows' => 4]]) ?>
    <div class="form-row">
      <?= form_field(['name' => 'category', 'label' => 'Category', 'type' => 'select', 'value' => $v['category'], 'required' => true, 'options' => categories(), 'errors' => $errors]) ?>
      <?= form_field(['name' => 'best_season', 'label' => 'Best time to go', 'value' => $v['best_season'], 'errors' => $errors, 'attrs' => ['maxlength' => 100]]) ?>
    </div>
    <div class="form-row">
      <?= form_field(['name' => 'price_per_person', 'label' => 'Price per person (' . cfg('currency_code') . ')', 'type' => 'number', 'value' => $v['price_per_person'], 'required' => true, 'errors' => $errors, 'attrs' => ['min' => 0, 'step' => '0.01', 'inputmode' => 'decimal']]) ?>
      <?= form_field(['name' => 'quantity', 'label' => 'Slots available', 'type' => 'number', 'value' => $v['quantity'], 'required' => true, 'errors' => $errors, 'attrs' => ['min' => 0, 'max' => 10000, 'step' => '1']]) ?>
    </div>
    <?= form_field(['name' => 'status', 'label' => 'Publication status', 'type' => 'select', 'value' => $v['status'], 'required' => true, 'options' => ['draft' => 'Draft (hidden)', 'published' => 'Published (visible)'], 'errors' => $errors]) ?>
  </div>
  <div class="panel">
    <h2>Image</h2>
    <div class="img-preview" id="imgPreview"><?= $preview ?></div>
    <?= form_field(['name' => 'image_path', 'label' => 'Image path', 'value' => $v['image_path'], 'errors' => $errors, 'hint' => 'Put your file in assets/images/destinations/ and enter its path, e.g. assets/images/destinations/destination-01.jpg', 'attrs' => ['maxlength' => 255]]) ?>
    <?= form_field(['name' => 'image_file', 'label' => 'Or upload an image (optional)', 'type' => 'file', 'errors' => $errors, 'hint' => 'JPG, PNG or WebP, up to ' . round(cfg('upload_max_bytes') / 1048576) . ' MB. An upload replaces the path above.', 'attrs' => ['accept' => 'image/jpeg,image/png,image/webp']]) ?>
  </div>
  <button class="btn btn--amber" type="submit"><?= $editId ? 'Save changes' : 'Create destination' ?></button>
</form>
<?php
    include ROOT_PATH . '/includes/admin-footer.php';
    exit;
}

// ---------- LIST VIEW ----------
$q = mb_substr(get_str('q'), 0, 60);
$cat = get_str('category'); if ($cat !== '' && !isset(categories()[$cat])) { $cat = ''; }
$status = get_str('status'); if (!in_array($status, ['published', 'draft'], true)) { $status = ''; }
$sorts = ['newest' => ['Newest', 'item_id DESC'], 'name' => ['Name', 'name ASC'], 'slots' => ['Slots (low first)', 'quantity ASC'], 'price' => ['Price (high first)', 'price_per_person DESC']];
$sort = get_str('sort', 'newest'); if (!isset($sorts[$sort])) { $sort = 'newest'; }
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(name LIKE ? OR location LIKE ?)'; array_push($args, like($q), like($q)); }
if ($cat !== '') { $where[] = 'category = ?'; $args[] = $cat; }
if ($status !== '') { $where[] = 'status = ?'; $args[] = $status; }
$whereSql = implode(' AND ', $where);
$c = $pdo->prepare("SELECT COUNT(*) FROM items WHERE $whereSql"); $c->execute($args);
$total = (int) $c->fetchColumn();
$perPage = (int) cfg('admin_per_page', 10);
[$page, $pages, $offset] = paginate($total, $perPage);
$st = $pdo->prepare("SELECT item_id, name, location, category, quantity, status, price_per_person, image_path FROM items WHERE $whereSql ORDER BY {$sorts[$sort][1]} LIMIT $perPage OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();
$params = keep_params(['q', 'category', 'status', 'sort']);

$pageTitle = 'Destination management'; $navActive = 'destinations';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title"><div><p class="eyebrow">Destinations</p><h1>Destination management</h1><p>Create, edit and publish the places travellers can book.</p></div>
  <a class="btn btn--amber btn--sm" href="<?= e(url('admin/destinations.php?action=new')) ?>">+ New destination</a></div>
<form class="admin-filter" method="get" role="search">
  <div class="field"><label for="q">Search name or location</label><input id="q" type="search" name="q" value="<?= e($q) ?>" maxlength="60"></div>
  <div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All</option><?php foreach (categories() as $k => $l): ?><option value="<?= e($k) ?>"<?= $cat === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All</option><option value="published"<?= $status === 'published' ? ' selected' : '' ?>>Published</option><option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>Draft</option></select></div>
  <div class="field"><label for="sort">Sort</label><select id="sort" name="sort"><?php foreach ($sorts as $k => [$l]): ?><option value="<?= e($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--amber btn--sm" type="submit">Apply</button>
</form>
<?php if (!$rows): ?>
<div class="empty"><h2><?= ($q !== '' || $cat !== '' || $status !== '') ? 'No matching destinations' : 'No destinations yet' ?></h2><p><?= ($q !== '' || $cat !== '' || $status !== '') ? 'Try another keyword or clear the filters.' : 'Create your first destination to get started.' ?></p>
  <a class="btn btn--amber" href="<?= e(url('admin/destinations.php?action=new')) ?>">New destination</a></div>
<?php else: ?>
<p class="muted"><?= $total ?> destination<?= $total === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Location</th><th>Category</th><th class="num">Slots</th><th class="num">Price</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
<?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int) $r['item_id'] ?></td>
      <td><span class="thumb-sm"><?= image_or_placeholder($r['image_path'], $r['name'], hue_for((int) $r['item_id']), 'cover') ?></span></td>
      <td><strong><?= e($r['name']) ?></strong></td>
      <td><?= e($r['location']) ?></td>
      <td><?= e(categories()[$r['category']] ?? $r['category']) ?></td>
      <td class="num"><?= (int) $r['quantity'] ?></td>
      <td class="num"><?= e(money($r['price_per_person'])) ?></td>
      <td><?= badge($r['status']) ?></td>
      <td><div class="row-actions">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/destinations.php?action=edit&id=' . (int) $r['item_id'])) ?>">Edit</a>
        <form method="post" class="inline-form" data-confirm="Delete &quot;<?= e($r['name']) ?>&quot;? This cannot be undone." data-confirm-label="Delete">
          <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="item_id" value="<?= (int) $r['item_id'] ?>">
          <button class="btn btn--danger btn--sm" type="submit">Delete</button>
        </form>
      </div></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table></div>
<?= pagination_html($page, $pages, $params) ?>
<?php endif; ?>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>
