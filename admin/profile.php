<?php
require __DIR__ . '/../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/profile.php';
$u = require_admin();
$errors = [];
if (is_post()) { csrf_verify(); $errors = profile_process($u, 'admin/profile.php'); }
$pageTitle = 'Admin profile'; $navActive = 'profile';
include ROOT_PATH . '/includes/admin-sidebar.php';
?>
<div class="admin-title"><div><p class="eyebrow">Account</p><h1>Admin profile</h1></div></div>
<div style="max-width:44rem"><?= profile_forms($u, $errors, post_str('form')) ?></div>
<?php include ROOT_PATH . '/includes/admin-footer.php'; ?>
