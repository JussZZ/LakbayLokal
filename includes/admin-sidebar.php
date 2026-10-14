<?php
/**
 * Admin shell (opens <main>). Every admin page calls require_admin() BEFORE including this.
 * Vars: $pageTitle, $navActive.
 */
$navActive = $navActive ?? '';
$bodyClass = 'admin-body';
$extraCss  = ['admin.css'];
include ROOT_PATH . '/includes/head.php';
$pendingUsers = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'pending'")->fetchColumn();
$adminMenu = [
    'dashboard'    => ['Admin Dashboard', 'admin/dashboard.php', null],
    'users'        => ['User Management', 'admin/users.php', null],
    'approvals'    => ['User Validation', 'admin/approvals.php', $pendingUsers ?: null],
    'destinations' => ['Destination Management', 'admin/destinations.php', null],
    'bookings'     => ['Booking Management', 'admin/bookings.php', null],
    'transactions' => ['Transaction Management', 'admin/transactions.php', null],
    'activity'     => ['Reports / Activity', 'admin/activity.php', null],
    'profile'      => ['Admin Profile', 'admin/profile.php', null],
];
?>
<div class="admin-shell">
  <aside class="admin-side" id="adminSide">
    <div class="admin-side__top">
      <a class="brand" href="<?= e(url('admin/dashboard.php')) ?>" aria-label="LakbayLokal admin dashboard">
        <img class="brand__logo" src="<?= e(asset('images/lakbaylokal-logo.png')) ?>" alt="" width="36" height="42">
        <span class="brand__name">LAKBAY<b>LOKAL</b></span>
      </a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="adminNav"><span class="nav-toggle__bars" aria-hidden="true"></span><span class="sr-only">Menu</span></button>
    </div>
    <nav id="adminNav" class="admin-side__nav" aria-label="Administrator">
      <p class="admin-side__label">Administration</p>
      <ul>
<?php foreach ($adminMenu as $key => [$label, $href, $count]): ?>
        <li><a href="<?= e(url($href)) ?>"<?= $navActive === $key ? ' class="is-active" aria-current="page"' : '' ?>><?= e($label) ?><?php if ($count): ?><span class="count"><?= (int) $count ?></span><?php endif; ?></a></li>
<?php endforeach; ?>
      </ul>
      <div class="admin-side__foot">
        <a href="<?= e(url('index.php')) ?>">View public site</a>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
          <?= csrf_field() ?>
          <button class="btn btn--ghost btn--sm" type="submit">Logout</button>
        </form>
      </div>
    </nav>
  </aside>
  <main class="admin-main" id="main">
