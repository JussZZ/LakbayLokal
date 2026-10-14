<?php
require __DIR__ . '/../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/profile.php';
$u = require_user();
$errors = [];
if (is_post()) { csrf_verify(); $errors = profile_process($u, 'user/profile.php'); }
$pageTitle = 'My profile'; $navActive = 'profile'; $extraCss = ['dashboard.css'];
include ROOT_PATH . '/includes/header.php';
?>
<main class="wrap page-pad" id="main">
  <div class="dash-head"><div><p class="eyebrow">Account</p><h1 class="display">My profile</h1></div></div>
  <div style="max-width:44rem"><?= profile_forms($u, $errors, post_str('form')) ?></div>
</main>

