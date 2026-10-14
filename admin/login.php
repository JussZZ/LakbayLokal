<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = current_user();
if ($u && $u['role'] === 'admin') { redirect('admin/dashboard.php'); }

$error = null; $old = '';
if (is_post()) {
    csrf_verify();
    $old = post_str('username');
    $password = (string) ($_POST['password'] ?? '');
    if ($old === '' || $password === '') {
        $error = 'Enter your username and password.';
    } elseif (attempt_login('admin', $old, $password, $error)) {     // stored role must be 'admin'
        log_activity((int) $_SESSION['uid'], 'admin.login', 'Administrator signed in');
        redirect('admin/dashboard.php');
    }
}
$art = auth_backdrop();
$pageTitle = 'Administrator login'; $extraCss = ['auth.css']; $bodyClass = 'auth--admin';
include ROOT_PATH . '/includes/header.php';
?>
<main class="auth auth--admin" id="main">
  <section class="auth__art">
    <div class="auth__art-bg<?= $art ? '' : ' is-placeholder' ?>"<?= $art ? ' style="background-image:url(\'' . e($art) . '\')"' : '' ?> aria-hidden="true"></div>
    <div class="auth__art-copy">
      <img class="auth__art-logo" src="<?= e(asset('images/lakbaylokal-logo.png')) ?>" alt="LakbayLokal logo">
      <h1>Administration</h1>
      <p>Validate travellers, manage destinations and review bookings.</p>
    </div>
  </section>
  <section class="auth__panel">
    <div class="auth__card">
      <span class="role-tag">Administrator access</span>
      <h2>Admin login</h2>
      <p class="sub">Restricted area. Traveller accounts cannot sign in here.</p>
<?php if ($error): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" data-validate>
        <?= csrf_field() ?>
        <?= form_field(['name' => 'username', 'label' => 'Username', 'value' => $old, 'required' => true, 'attrs' => ['autocomplete' => 'username', 'maxlength' => 50]]) ?>
        <?= form_field(['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]) ?>
        <button class="btn btn--amber btn--block" type="submit">Sign in</button>
      </form>
      <p class="auth__alt">Not an administrator? <a href="<?= e(url('user/login.php')) ?>">Traveller login</a></p>
    </div>
  </section>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
