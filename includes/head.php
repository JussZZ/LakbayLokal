<?php
/** Opens <html>, <head> and the toast container. Expects $pageTitle, $bodyClass, $extraCss. */
$pageTitle = $pageTitle ?? cfg('app_name');
$bodyClass = $bodyClass ?? '';
$extraCss  = $extraCss ?? [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= e(cfg('app_name')) ?></title>
  <meta name="description" content="LakbayLokal: discover local destinations and send booking requests.">
  <link rel="icon" href="<?= e(asset('images/lakbaylokal-logo.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= e(asset('css/' . $css)) ?>">
<?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="topline" aria-hidden="true"></div>
<div class="toasts" aria-live="polite">
<?php foreach (take_flashes() as $f): ?>
  <div class="toast toast--<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
    <span><?= e($f['msg']) ?></span>
    <button type="button" class="toast__close" aria-label="Dismiss message" data-dismiss>&times;</button>
  </div>
<?php endforeach; ?>
</div>
