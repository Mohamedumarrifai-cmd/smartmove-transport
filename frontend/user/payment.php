<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'payments';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Payments | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="payments">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / PAYMENTS</p><h1>Your payment records.</h1></div>
				<a class="panel-button panel-button-coral" href="my-bookings.php">View bookings <span aria-hidden="true">↗</span></a>
			</header>
			<div class="panel-content">
				<section class="page-intro-line"><div><p class="panel-eyebrow">TRAVEL SPENDING</p><h2>Payment history</h2><p>Payments recorded for your SmartMove tickets.</p></div><span class="history-count" data-payment-count>— <small>RECORDS</small></span></section>
				<p class="panel-feedback" data-payment-feedback role="status" aria-live="polite"></p>
				<div class="payment-list" data-payment-list aria-live="polite"><div class="results-loading"><span class="loading-line"></span><span class="loading-line"></span></div></div>
				<p class="payment-note"><span class="material-symbols-rounded" aria-hidden="true">info</span> Online payment processing is not enabled yet. This page shows payment records already registered to your tickets.</p>
			</div>
		</main>
	</div>
</body>
</html>
