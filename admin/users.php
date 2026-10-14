<?php
// Shared by users.php (all accounts) and approvals.php (pending only).
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pdo = db();
$mode = $mode ?? 'users';
$self = $mode === 'approvals' ? 'admin/approvals.php' : 'admin/users.php';

// ---- status change (POST) ----
if (is_post()) {
    csrf_verify();
    $id = post_int('id'); $action = post_str('action');
    $map  = ['approve' => 'approved', 'reject' => 'rejected', 'suspend' => 'suspended', 'reactivate' => 'approved'];
    $from = ['approve' => ['pending', 'rejected'], 'reject' => ['pending'], 'suspend' => ['approved'], 'reactivate' => ['suspended']];
    if (!isset($map[$action])) {
        flash('error', 'Unknown action.');
    } else {
        $st = $pdo->prepare('SELECT user_id, username, role, status FROM users WHERE user_id = :id');
        $st->execute([':id' => $id]);
        $t = $st->fetch();
        if (!$t) {
            flash('error', 'Account not found.');
        } elseif ($t['role'] !== 'user' || (int) $t['user_id'] === (int) $admin['user_id']) {
            flash('error', 'Administrator accounts are protected and cannot be changed here.');
        } elseif (!in_array($t['status'], $from[$action], true)) {
            flash('error', 'That account is already ' . $t['status'] . '. Refresh and try again.');
        } else {
            $up = $pdo->prepare("UPDATE users SET status = :s WHERE user_id = :id AND role = 'user'");
            $up->execute([':s' => $map[$action], ':id' => $id]);
            log_activity((int) $admin['user_id'], 'user.' . $action, $t['username'] . ': ' . $t['status'] . ' -> ' . $map[$action]);
            flash('success', 'Account "' . $t['username'] . '" is now ' . $map[$action] . '.');
        }
    }
    parse_str(post_str('back'), $b);
    $clean = [];
    foreach (['q', 'status', 'sort', 'page'] as $key) { if (isset($b[$key]) && is_string($b[$key]) && $b[$key] !== '') { $clean[$key] = $b[$key]; } }
    redirect($self . ($clean ? '?' . http_build_query($clean) : ''));
}

// ---- listing (GET) ----
$q = mb_substr(get_str('q'), 0, 60);
$status = $mode === 'approvals' ? 'pending' : get_str('status');
if ($status !== '' && !in_array($status, user_statuses(), true)) { $status = ''; }
$sorts = ['newest' => ['Newest first', 'u.created_at DESC'], 'username' => ['Username', 'u.username ASC'], 'status' => ['Status', 'u.status ASC, u.created_at DESC']];
$sort = get_str('sort', 'newest'); if (!isset($sorts[$sort])) { $sort = 'newest'; }

$where = ['1=1']; $args = [];
if ($mode === 'approvals') { $where[] = "u.role = 'user'"; }
if ($status !== '') { $where[] = 'u.status = ?'; $args[] = $status; }
if ($q !== '') { $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)'; array_push($args, like($q), like($q), like($q)); }
$whereSql = implode(' AND ', $where);

$c = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSql"); $c->execute($args);
$total = (int) $c->fetchColumn();
$perPage = (int) cfg('admin_per_page', 10);
[$page, $pages, $offset] = paginate($total, $perPage);

// LEFT JOIN users -> bookings gives each account's booking count.
$st = $pdo->prepare("SELECT u.user_id, u.username, u.full_name, u.email, u.phone, u.role, u.status, u.created_at, COUNT(b.booking_id) AS bookings
                       FROM users u LEFT JOIN bookings b ON b.user_id = u.user_id
                      WHERE $whereSql GROUP BY u.user_id ORDER BY {$sorts[$sort][1]} LIMIT $perPage OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();
$params = keep_params(['q', 'status', 'sort']);
$back = http_build_query($params + ($page > 1 ? ['page' => $page] : []));

function user_action_form(array $r, string $action, string $label, string $btn, ?string $confirm, string $back): string
{
    return '<form method="post" class="inline-form"' . ($confirm ? ' data-confirm="' . e($confirm) . '" data-confirm-label="' . e($label) . '"' : '') . '>'
        . csrf_field() . '<input type="hidden" name="id" value="' . (int) $r['user_id'] . '"><input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="back" value="' . e($back) . '"><button class="btn ' . $btn . ' btn--sm" type="submit">' . e($label) . '</button></form>';
}

$pageTitle = $mode === 'approvals' ? 'User validation' : 'User management';
$navActive = $mode === 'approvals' ? 'approvals' : 'users';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title"><div><p class="eyebrow"><?= $mode === 'approvals' ? 'Validation' : 'Accounts' ?></p><h1><?= e($pageTitle) ?></h1>
  <p><?= $mode === 'approvals' ? 'Approve or reject newly registered travellers. Pending accounts cannot book.' : 'Search accounts, review their status and manage access.' ?></p></div></div>

<form class="admin-filter" method="get" role="search">
  <div class="field"><label for="q">Search username, name or email</label><input id="q" type="search" name="q" value="<?= e($q) ?>" maxlength="60"></div>
<?php if ($mode !== 'approvals'): ?>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All</option>
<?php foreach (user_statuses() as $s): ?><option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
<?php endif; ?>
  <div class="field"><label for="sort">Sort</label><select id="sort" name="sort"><?php foreach ($sorts as $k => [$l]): ?><option value="<?= e($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--amber btn--sm" type="submit">Apply</button>
</form>

<?php if (!$rows): ?>
<div class="empty"><h2><?= ($q !== '' || $status !== '') && $mode !== 'approvals' || $q !== '' ? 'No matching accounts' : ($mode === 'approvals' ? 'All caught up' : 'No accounts yet') ?></h2>
  <p><?= $mode === 'approvals' && $q === '' ? 'No accounts are waiting for approval.' : 'Try another keyword or clear the filters.' ?></p></div>
<?php else: ?>
<p class="muted"><?= $total ?> account<?= $total === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>ID</th><th>Username</th><th>Full name</th><th>Email</th><th>Role</th><th>Status</th><th class="num">Bookings</th><th>Registered</th><th>Actions</th></tr></thead>
  <tbody>
<?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int) $r['user_id'] ?></td>
      <td><strong><?= e($r['username']) ?></strong></td>
      <td><?= e($r['full_name']) ?></td>
      <td><?= e($r['email']) ?></td>
      <td><?= badge($r['role']) ?></td>
      <td><?= badge($r['status']) ?></td>
      <td class="num"><?= (int) $r['bookings'] ?></td>
      <td><?= e(fmt_date($r['created_at'])) ?></td>
      <td><div class="row-actions">
<?php if ($r['role'] !== 'user'): ?>
        <span class="muted">Protected</span>
<?php else:
    $n = $r['username'];
    if (in_array($r['status'], ['pending', 'rejected'], true)) { echo user_action_form($r, 'approve', 'Approve', 'btn--amber', 'Approve account "' . $n . '"? They will be able to place bookings.', $back); }
    if ($r['status'] === 'pending') { echo user_action_form($r, 'reject', 'Reject', 'btn--danger', 'Reject account "' . $n . '"? They will not be able to log in.', $back); }
    if ($r['status'] === 'approved') { echo user_action_form($r, 'suspend', 'Suspend', 'btn--danger', 'Suspend account "' . $n . '"? They will lose booking access.', $back); }
    if ($r['status'] === 'suspended') { echo user_action_form($r, 'reactivate', 'Reactivate', 'btn--amber', 'Reactivate account "' . $n . '"?', $back); }
endif; ?>
      </div></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table></div>
<?= pagination_html($page, $pages, $params) ?>
<?php endif; ?>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>
