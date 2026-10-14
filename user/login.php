<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = current_user();
if ($u && $u['role'] === 'user') { redirect('user/dashboard.php'); }

$error = null; $old = '';
$next = safe_next(get_str('next') ?: post_str('next'));
if (is_post()) {
    csrf_verify();
    $old = post_str('username');
    $password = (string) ($_POST['password'] ?? '');
    if ($old === '' || $password === '') {
        $error = 'Enter your username and password.';
    } elseif (attempt_login('user', $old, $password, $error)) {   // role must be 'user' for this route
        flash('success', 'Welcome back!');
        redirect($next ?: 'user/dashboard.php');
    }
}
$art = auth_backdrop();
$pageTitle = 'Traveller login'; $navActive = 'login'; $extraCss = ['auth.css'];
include ROOT_PATH . '/includes/header.php';
?>
<main class="auth" id="main">
  <section class="auth__art">
    <div class="auth__art-bg<?= $art ? '' : ' is-placeholder' ?>"<?= $art ? ' style="background-image:url(\'' . e($art) . '\')"' : '' ?> aria-hidden="true"></div>
    <div class="auth__art-copy">
      <img class="auth__art-logo" src="<?= e(asset('images/lakbaylokal-logo.png')) ?>" alt="LakbayLokal logo">
      <h1>Welcome back, traveller</h1>
      <p>Log in to follow your booking requests and plan your next trip.</p>
    </div>
  </section>
  <section class="auth__panel">
    <div class="auth__card">
      <h2>Log in</h2>
      <p class="sub">Use your LakbayLokal username and password.</p>
<?php if ($error): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <?= form_field(['name' => 'username', 'label' => 'Username', 'value' => $old, 'required' => true, 'attrs' => ['autocomplete' => 'username', 'maxlength' => 50]]) ?>
        <?= form_field(['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]) ?>
        <button class="btn btn--amber btn--block" type="submit">Log in</button>
      </form>
      <p class="auth__alt">No account yet? <a href="<?= e(url('user/register.php')) ?>">Register</a><br>Administrator? <a href="<?= e(url('admin/login.php')) ?>">Admin login</a></p>
    </div>
  </section>
</main>

