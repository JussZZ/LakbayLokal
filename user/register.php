<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = current_user();
if ($u && $u['role'] === 'user') { redirect('user/dashboard.php'); }

$errors = []; $v = ['username' => '', 'full_name' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    csrf_verify();
    foreach ($v as $k => $_) { $v[$k] = post_str($k); }
    $pw = (string) ($_POST['password'] ?? ''); $pw2 = (string) ($_POST['password_confirm'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $v['username'])) { $errors['username'] = 'Username must be 4-30 characters: letters, numbers or underscore.'; }
    if ($v['full_name'] === '' || mb_strlen($v['full_name']) < 2 || mb_strlen($v['full_name']) > 120) { $errors['full_name'] = 'Enter your full name (2-120 characters).'; }
    if (!valid_email($v['email'])) { $errors['email'] = 'Enter a valid email address.'; }
    if ($v['phone'] !== '' && !valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number.'; }
    if ($p = password_problem($pw)) { $errors['password'] = $p; }
    elseif ($pw !== $pw2) { $errors['password_confirm'] = 'Passwords do not match.'; }

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE username = :u OR email = :e LIMIT 1');
        $st->execute([':u' => $v['username'], ':e' => $v['email']]);
        if ($st->fetchColumn()) { $errors['username'] = 'That username or email is already registered.'; }
    }
    if (!$errors) {
        try {
            // Role and status are fixed by the server: visitors can never choose them.
            $st = db()->prepare("INSERT INTO users (username, password, role, full_name, email, phone, status)
                                 VALUES (:u, :p, 'user', :n, :e, :ph, 'pending')");
            $st->execute([':u' => $v['username'], ':p' => password_hash($pw, PASSWORD_DEFAULT), ':n' => $v['full_name'], ':e' => $v['email'], ':ph' => $v['phone'] !== '' ? $v['phone'] : null]);
            log_activity((int) db()->lastInsertId(), 'user.register', 'New account registered: ' . $v['username']);
            flash('success', 'Account created. An administrator must approve it before you can book. You can log in now.');
            redirect('user/login.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { $errors['username'] = 'That username or email is already registered.'; }
            else { throw $ex; }
        }
    }
}
$art = auth_backdrop();
$pageTitle = 'Register'; $navActive = 'login'; $extraCss = ['auth.css'];
include ROOT_PATH . '/includes/header.php';
?>
<main class="auth" id="main">
  <section class="auth__art">
    <div class="auth__art-bg<?= $art ? '' : ' is-placeholder' ?>"<?= $art ? ' style="background-image:url(\'' . e($art) . '\')"' : '' ?> aria-hidden="true"></div>
    <div class="auth__art-copy">
      <img class="auth__art-logo" src="<?= e(asset('images/lakbaylokal-logo.png')) ?>" alt="LakbayLokal logo">
      <h1>Start your lakbay</h1>
      <p>Create an account to send booking requests. New accounts are reviewed and approved by an administrator.</p>
    </div>
  </section>
  <section class="auth__panel">
    <div class="auth__card">
      <h2>Create account</h2>
      <p class="sub">Fields marked * are required.</p>
      <?= error_summary($errors) ?>
      <form method="post" data-validate>
        <?= csrf_field() ?>
        <?= form_field(['name' => 'full_name', 'label' => 'Full name', 'value' => $v['full_name'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 120, 'autocomplete' => 'name']]) ?>
        <?= form_field(['name' => 'username', 'label' => 'Username', 'value' => $v['username'], 'required' => true, 'errors' => $errors, 'hint' => '4-30 characters: letters, numbers, underscore.', 'attrs' => ['maxlength' => 30, 'pattern' => '[A-Za-z0-9_]{4,30}', 'data-pattern-message' => 'Use 4-30 letters, numbers or underscores.', 'autocomplete' => 'username']]) ?>
        <?= form_field(['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $v['email'], 'required' => true, 'errors' => $errors, 'attrs' => ['maxlength' => 150, 'autocomplete' => 'email']]) ?>
        <?= form_field(['name' => 'phone', 'label' => 'Phone (optional)', 'type' => 'tel', 'value' => $v['phone'], 'errors' => $errors, 'attrs' => ['maxlength' => 20, 'autocomplete' => 'tel', 'pattern' => '[0-9+\(\)\-\s]{7,20}', 'data-pattern-message' => 'Use digits, spaces, + ( ) or - (7-20 characters).']]) ?>
        <?= form_field(['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'errors' => $errors, 'hint' => 'At least 8 characters with a letter and a number.', 'attrs' => ['autocomplete' => 'new-password', 'data-password' => true]]) ?>
        <?= form_field(['name' => 'password_confirm', 'label' => 'Confirm password', 'type' => 'password', 'required' => true, 'errors' => $errors, 'attrs' => ['autocomplete' => 'new-password', 'data-match' => 'f_password']]) ?>
        <button class="btn btn--amber btn--block" type="submit">Register</button>
      </form>
      <p class="auth__alt">Already registered? <a href="<?= e(url('user/login.php')) ?>">Log in</a></p>
    </div>
  </section>
</main>

