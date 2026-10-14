<?php $contact = cfg('contact'); ?>
<footer class="site-footer">
  <div class="wrap site-footer__grid">
    <div class="site-footer__brand">
      <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="LakbayLokal home">
        <img class="brand__logo brand__logo--lg" src="<?= e(asset('images/lakbaylokal-logo.png')) ?>" alt="" width="56" height="64">
        <span class="brand__name">LAKBAY<b>LOKAL</b></span>
      </a>
      <p>Discover local destinations and send booking requests in one place.</p>
    </div>
    <nav aria-label="Footer">
      <h2 class="footer-h">Explore</h2>
      <ul>
        <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
        <li><a href="<?= e(url('destinations.php')) ?>">Destinations</a></li>
        <li><a href="<?= e(url('about.php')) ?>">About LakbayLokal</a></li>
        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
      </ul>
    </nav>
    <div>
      <h2 class="footer-h">Account</h2>
      <ul>
        <li><a href="<?= e(url('user/login.php')) ?>">Traveller login</a></li>
        <li><a href="<?= e(url('user/register.php')) ?>">Register</a></li>
        <li><a href="<?= e(url('admin/login.php')) ?>">Administrator login</a></li>
      </ul>
    </div>
    <div>
      <h2 class="footer-h">Contact</h2>
      <ul class="muted-list">
        <li><?= e($contact['email']) ?></li>
        <li><?= e($contact['phone']) ?></li>
        <li><?= e($contact['address']) ?></li>
      </ul>
    </div>
  </div>
  <div class="wrap site-footer__base">
    <span>&copy; <?= date('Y') ?> LakbayLokal</span>
    
  </div>
</footer>
<?php include ROOT_PATH . '/includes/scripts.php'; ?>
