<?php
/** Shared profile logic for travellers and administrators (details + password change). */
function profile_process(array $user, string $returnPath): array
{
    $errors = []; $form = post_str('form');
    if ($form === 'details') {
        $name = post_str('full_name'); $email = post_str('email'); $phone = post_str('phone');
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 120) { $errors['full_name'] = 'Enter your full name (2-120 characters).'; }
        if (!valid_email($email)) { $errors['email'] = 'Enter a valid email address.'; }
        if ($phone !== '' && !valid_phone($phone)) { $errors['phone'] = 'Enter a valid phone number.'; }
        if (!$errors) {
            $st = db()->prepare('SELECT 1 FROM users WHERE email = :e AND user_id <> :id');
            $st->execute([':e' => $email, ':id' => $user['user_id']]);
            if ($st->fetchColumn()) { $errors['email'] = 'That email is already used by another account.'; }
        }
        if (!$errors) {
            $st = db()->prepare('UPDATE users SET full_name = :n, email = :e, phone = :p WHERE user_id = :id');
            $st->execute([':n' => $name, ':e' => $email, ':p' => $phone !== '' ? $phone : null, ':id' => $user['user_id']]);
            log_activity((int) $user['user_id'], 'profile.update', 'Profile details updated');
            flash('success', 'Your profile has been updated.');
            redirect($returnPath);
        }
    } elseif ($form === 'password') {
        $cur = (string) ($_POST['current_password'] ?? ''); $new = (string) ($_POST['new_password'] ?? ''); $conf = (string) ($_POST['confirm_password'] ?? '');
        $st = db()->prepare('SELECT password FROM users WHERE user_id = :id');
        $st->execute([':id' => $user['user_id']]);
        if (!password_verify($cur, (string) $st->fetchColumn())) { $errors['current_password'] = 'Your current password is incorrect.'; }
        if ($p = password_problem($new)) { $errors['new_password'] = $p; }
        elseif ($new !== $conf) { $errors['confirm_password'] = 'Passwords do not match.'; }
        if (!$errors) {
            $st = db()->prepare('UPDATE users SET password = :p WHERE user_id = :id');
            $st->execute([':p' => password_hash($new, PASSWORD_DEFAULT), ':id' => $user['user_id']]);
            session_regenerate_id(true);
            log_activity((int) $user['user_id'], 'profile.password', 'Password changed');
            flash('success', 'Your password has been changed.');
            redirect($returnPath);
        }
    }
    return $errors;
}

function profile_forms(array $user, array $errors, string $postedForm): string
{
    $d = ($postedForm === 'details') ? ['full_name' => post_str('full_name'), 'email' => post_str('email'), 'phone' => post_str('phone')]
                                     : ['full_name' => $user['full_name'], 'email' => $user['email'], 'phone' => (string) $user['phone']];
    $eD = $postedForm === 'details' ? $errors : []; $eP = $postedForm === 'password' ? $errors : [];
    ob_start(); ?>
<div class="panel">
  <h2>Account details</h2>
  <p class="muted">Username: <strong><?= e($user['username']) ?></strong> &middot; Role: <?= badge($user['role']) ?> &middot; Status: <?= badge($user['status']) ?></p>
  <?= error_summary($eD) ?>
  <form method="post" data-validate>
    <?= csrf_field() ?><input type="hidden" name="form" value="details">
    <?= form_field(['name' => 'full_name', 'label' => 'Full name', 'value' => $d['full_name'], 'required' => true, 'errors' => $eD, 'attrs' => ['maxlength' => 120, 'autocomplete' => 'name']]) ?>
    <div class="form-row">
      <?= form_field(['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $d['email'], 'required' => true, 'errors' => $eD, 'attrs' => ['maxlength' => 150, 'autocomplete' => 'email']]) ?>
      <?= form_field(['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'value' => $d['phone'], 'errors' => $eD, 'attrs' => ['maxlength' => 20, 'autocomplete' => 'tel', 'pattern' => '[0-9+\(\)\-\s]{7,20}', 'data-pattern-message' => 'Use digits, spaces, + ( ) or - (7-20 characters).']]) ?>
    </div>
    <button class="btn btn--amber" type="submit">Save changes</button>
  </form>
</div>
<div class="panel" style="margin-top:1.5rem">
  <h2>Change password</h2>
  <?= error_summary($eP) ?>
  <form method="post" data-validate>
    <?= csrf_field() ?><input type="hidden" name="form" value="password">
    <?= form_field(['name' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'required' => true, 'errors' => $eP, 'attrs' => ['autocomplete' => 'current-password']]) ?>
    <div class="form-row">
      <?= form_field(['name' => 'new_password', 'label' => 'New password', 'type' => 'password', 'required' => true, 'errors' => $eP, 'hint' => 'At least 8 characters with a letter and a number.', 'attrs' => ['autocomplete' => 'new-password', 'data-password' => true]]) ?>
      <?= form_field(['name' => 'confirm_password', 'label' => 'Confirm new password', 'type' => 'password', 'required' => true, 'errors' => $eP, 'attrs' => ['autocomplete' => 'new-password', 'data-match' => 'f_new_password']]) ?>
    </div>
    <button class="btn btn--ghost" type="submit">Update password</button>
  </form>
</div>
<?php
    return ob_get_clean();
}
