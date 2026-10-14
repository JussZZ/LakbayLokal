<?php
$u = current_user();
$navActive  = $navActive ?? '';
$navOverlay = !empty($navOverlay);
include ROOT_PATH . '/includes/head.php';

if (!$u || $u['role'] === 'admin') {
  $menu = [
    'home'         => ['Home', 'index.php'],
    'destinations' => ['Destinations', 'destinations.php'],
    'about'        => ['About LakbayLokal', 'about.php'],
    'contact'      => ['Contact', 'contact.php'],
  ];
} else {
  $menu = [
    'home'         => ['Home', 'index.php'],
    'dashboard'    => ['User Dashboard', 'user/dashboard.php'],
    'destinations' => ['Explore Destinations', 'destinations.php'],
    'bookings'     => ['My Bookings', 'user/bookings.php'],
    'profile'      => ['My Profile', 'user/profile.php'],
  ];
}
?>
<header class="site-nav<?= $navOverlay ? ' site-nav--overlay' : '' ?>">
  <div class="site-nav__inner">
    <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="LakbayLokal home">
      <img class="brand__logo" src="<?= e(asset('images/lakbaylokal-logo.png')) ?>" alt="" width="40" height="46">
      <span class="brand__name">LAKBAY<b>LOKAL</b></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="siteMenu">
      <span class="nav-toggle__bars" aria-hidden="true"></span><span class="sr-only">Menu</span>
    </button>
    <nav class="site-nav__menu" id="siteMenu" aria-label="Main">
      <ul>
        <?php foreach ($menu as $key => [$label, $href]): ?>
          <li><a href="<?= e(url($href)) ?>" <?= $navActive === $key ? ' class="is-active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="site-nav__actions">
        <?php if (!$u): ?>
          <a class="nav-link<?= $navActive === 'login' ? ' is-active' : '' ?>" href="<?= e(url('user/login.php')) ?>">Login</a>
          <a class="btn btn--amber btn--sm" href="<?= e(url('user/register.php')) ?>">Register</a>
        <?php else: ?>
          <?php if ($u['role'] === 'admin'): ?>
            <a class="nav-link" href="<?= e(url('admin/dashboard.php')) ?>">Admin Panel</a>
          <?php endif; ?>
          <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <button class="btn btn--ghost btn--sm" type="submit">Logout</button>
          </form>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>