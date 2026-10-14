<?php
require __DIR__ . '/includes/bootstrap.php';
if (!is_post()) { redirect('index.php'); }
csrf_verify();
logout_user();
// Start a fresh session just to carry the goodbye message.
session_start();
session_regenerate_id(true);
flash('success', 'You have been logged out.');
redirect('index.php');
