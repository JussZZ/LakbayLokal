<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About LakbayLokal'; $navActive = 'about';
include ROOT_PATH . '/includes/header.php';
?>
<section class="page-hero"><div class="wrap">
  <p class="eyebrow">About LakbayLokal</p>
  <h1 class="display">Travel that starts nearby</h1>
  <p class="lead">&ldquo;Lakbay&rdquo; means journey. LakbayLokal helps people find local destinations and send booking requests that a real person reviews.</p>
</div></section>
<main class="wrap page-pad two-col" id="main">
  <div class="prose">
    <h2>What the platform does</h2>
    <p>Visitors can browse destinations, read the details and register for an account. Approved travellers send booking requests, follow their status and keep a history of past trips.</p>
    <h2>How it works</h2>
    <ul>
      <li>Create an account. An administrator approves it.</li>
      <li>Choose a destination, a travel date and your group size.</li>
      <li>We review your request and update its status to confirmed, cancelled or completed.</li>
    </ul>
    <p><a class="btn btn--amber arrow" href="<?= e(url('destinations.php')) ?>">Explore destinations</a></p>
  </div>
  <div class="panel">
    <h2>Good to know</h2>
    <p class="muted">LakbayLokal records booking requests and their status. It does not process online payments, and amounts shown are for reference until payment arrangements are confirmed with the team.</p>
    <p class="muted">Team and company information will be added here by the site owner.</p>
  </div>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
