<?php
require_once __DIR__ . '/../includes/user-panel-auth.php';
$activePage = 'payments';
$paymentId = filter_var($_GET['payment_id'] ?? null, FILTER_VALIDATE_INT);
$paymentId = $paymentId !== false && $paymentId > 0 ? $paymentId : null;
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1A365D">
	<title>Payment receipt | SmartMove</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/user-panel.css">
	<script src="../assets/js/user-panel.js" defer></script>
</head>
<body class="panel-page" data-page="payment-success" data-payment-id="<?= htmlspecialchars((string) $paymentId, ENT_QUOTES, 'UTF-8') ?>">
	<div class="panel-shell">
		<?php require __DIR__ . '/../includes/user-sidebar.php'; ?>
		<main class="panel-main">
			<header class="panel-topbar">
				<div><p class="panel-eyebrow">PASSENGER / PAYMENT RECEIPT</p><h1>Payment complete.</h1></div>
				<a class="panel-button panel-button-coral" href="my-bookings.php">View bookings <span aria-hidden="true">↗</span></a>
			</header>
			<div class="panel-content">
				<p class="panel-feedback" data-receipt-feedback role="status" aria-live="polite"></p>
				<section class="payment-receipt" data-payment-receipt hidden>
					<div class="receipt-success-mark" aria-hidden="true"><span class="material-symbols-rounded">check</span></div>
					<p class="panel-eyebrow">SMARTMOVE PAYMENT</p>
					<h2>Thank you for your payment.</h2>
					<p class="receipt-total" data-receipt-amount>—</p>
					<dl class="receipt-details">
						<div><dt>Transaction reference</dt><dd data-receipt-reference>—</dd></div>
						<div><dt>Payment method</dt><dd data-receipt-method>—</dd></div>
						<div><dt>Date and time</dt><dd data-receipt-time>—</dd></div>
						<div><dt>Payment ID</dt><dd data-receipt-payment-id>—</dd></div>
					</dl>
					<a class="panel-button panel-button-coral" href="payment.php">Payment history <span aria-hidden="true">↗</span></a>
				</section>
			</div>
		</main>
	</div>
</body>
</html>
